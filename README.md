# Projeto de Auto-Scaling com Kubernetes e Datadog

## Visão Geral

Este projeto demonstra como implementar auto-scaling (HPA - Horizontal Pod Autoscaler) no Kubernetes, utilizando uma métrica customizada de uma aplicação PHP enviada para o Datadog.

A arquitetura cria um ciclo de feedback onde o volume de requisições na aplicação, monitorado pelo Datadog, dita dinamicamente o número de réplicas da própria aplicação, otimizando o uso de recursos.

## Arquitetura

O fluxo de funcionamento é o seguinte:

```
Requisições      ┌───────────────────┐      Métricas     ┌────────────────┐      Query      ┌───────────────────┐
──────────────> │   Aplicação PHP   ├─────────────────> │ Datadog Agent  ├───────────> │ Plataforma Datadog│
                  └───────────────────┘                   └────────────────┘                 └──────────▲────────┘
                        ▲                                                                           │
                        │ Escala (cria/remove pods)                                                 │ Pede o valor da métrica
                        │                                                                           │
                  ┌─────┴─────┐                   ┌──────────────────────────┐                    ┌─┴─────────────────────┐
                  │    HPA    │ <──────────────── │ Kube Metrics API Server  │ <────────────────── │ Datadog Cluster Agent │
                  └───────────┘    Pede a métrica   └──────────────────────────┘     Fornece a métrica    └───────────────────────┘
                                  `app-php-scaling`
```

#### Componentes Principais:

1.  **Aplicação PHP (`index.php`, `Dockerfile`, `deployment.yaml`)**: A aplicação que, a cada requisição, envia a métrica `php.request.count` via DogStatsD. Sua implantação no Kubernetes é gerenciada pelo `deployment.yaml`.
2.  **Datadog Agent**: Instalado como um `DaemonSet` em cada nó do cluster, ele coleta métricas dos pods (incluindo a `php.request.count`) e as encaminha para a plataforma Datadog.
3.  **Datadog Cluster Agent**: Um `Deployment` centralizado que atua como um "Provedor de Métricas Externas" (`External Metrics Provider`). Ele expõe métricas da plataforma Datadog para a API de métricas do Kubernetes.
4.  **DatadogMetric (`datadogmetric.yaml`)**: Um recurso customizado que instrui o Cluster Agent sobre qual métrica do Datadog expor (`sum:php.request.count...`) e com qual nome (`app-php-scaling`).
5.  **HorizontalPodAutoscaler (HPA) (`hpa.yaml`)**: O controlador do Kubernetes que monitora a métrica `app-php-scaling` e ajusta o número de réplicas do `Deployment` da aplicação PHP para cima ou para baixo, com base no valor alvo configurado.

## Instalação e Setup

Siga os passos abaixo para configurar e implantar o projeto.

**Pré-requisitos:**
*   Acesso a um cluster Kubernetes.
*   `kubectl` instalado e configurado.
*   `helm` v3 instalado.

---

#### Passo 1: Criar o Secret com as Chaves do Datadog

Antes de tudo, o Datadog precisa de suas chaves de API e de Aplicação. Crie um arquivo `datadog-secret.yaml` com o seguinte conteúdo, substituindo `<SUA_API_KEY>` e `<SUA_APP_KEY>`:

```yaml
# datadog-secret.yaml
apiVersion: v1
kind: Secret
metadata:
  name: datadog-secret
type: Opaque
data:
  api-key: <SUA_API_KEY_EM_BASE64>
  app-key: <SUA_APP_KEY_EM_BASE64>
```
**Importante**: Os valores das chaves devem estar codificados em **Base64**. Você pode codificá-los com o comando:
`echo -n "SUA_CHAVE_AQUI" | base64`

Aplique o secret no cluster:
```bash
kubectl create namespace app-php
kubectl apply -f datadog-secret.yaml -n app-php
```

---

#### Passo 2: Instalar os Componentes Datadog com Helm

Com as chaves no cluster, instale o Datadog Agent e o Cluster Agent usando Helm. O arquivo `datadog-values.yaml` já está configurado para habilitar os recursos necessários.

```bash
# Adicione o repositório Helm do Datadog
helm repo add datadog https://helm.datadoghq.com
helm repo update

# Crie o namespace para o Datadog e instale o chart
kubectl create namespace datadog
helm upgrade --install datadog datadog/datadog 
  -n datadog 
  -f datadog-values.yaml
```
O `datadog-values.yaml` está configurado para que o Datadog busque o `datadog-secret` criado no passo anterior.

---

#### Passo 3: Implantar a Aplicação e o HPA

Agora, implante todos os manifestos Kubernetes relacionados à aplicação e ao auto-scaling.

```bash
# Aplica o Deployment, Service, DatadogMetric e HPA
kubectl apply -f .
```
Este comando irá criar:
*   O `Deployment` e `Service` da aplicação PHP no namespace `app-php`.
*   O recurso `DatadogMetric` que expõe a métrica de contagem de requisições.
*   O `HPA` que monitora essa métrica e gerencia o scaling do `Deployment`.

---

#### Passo 4: Gerar Carga para Testes

Para testar o auto-scaling, você precisa gerar tráfego para a aplicação. O `Service` está exposto via `NodePort` na porta `30080`.

Use um loop para fazer requisições:
```bash
# Execute este comando para enviar 100 requisições
for i in {1..100}; do curl -s http://<IP_DE_UM_NO_DO_CLUSTER>:30080/ > /dev/null && echo "Request $i sent"; done
```

---

#### Passo 5: Validar o Auto-scaling

Verifique o status do HPA para observar o scaling em ação.
```bash
# Descreve o HPA e mostra os eventos de scaling
kubectl describe hpa -n app-php

# Observa o HPA em tempo real
kubectl get hpa -n app-php -w
```
No painel do Datadog, você poderá ver a métrica `php.request.count` subindo e, no Kubernetes, o número de pods da aplicação `app-php` aumentando para atender à demanda.

## Comandos Úteis

#### Verificar status e logs
```bash
# Logs da aplicação
kubectl logs -n app-php -l app=app-php --tail=50

# Logs do Datadog Agent (para ver se traces/métricas estão chegando)
kubectl logs -n datadog -l app=datadog --tail=100

# Diagnóstico completo do Agent
kubectl exec -it <agent-pod-name> -n datadog -- agent diagnose
```

#### Forçar a quantidade de réplicas (para resetar o teste)
```bash
kubectl scale deployment app-php -n app-php --replicas=1
```

#### Enviar uma métrica de teste via DogStatsD (dentro de um pod no cluster)
```bash
# O endereço 'datadog.datadog.svc.cluster.local' aponta para o serviço do Agent
echo "test.metric:1|c" | nc -u -w0 datadog.datadog.svc.cluster.local 8125
```
