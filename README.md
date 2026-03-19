## Visão Geral do Ambiente

Este ambiente de testes publica uma aplicação PHP simples no namespace `app-php`. O arquivo `deployment.yaml` cria o `Deployment` e o `Service`, habilita a instrumentação do Datadog por admission controller e expõe a aplicação pela porta `30080`.

A imagem é definida no arquivo `Dockerfile`, usando `php:8.3-cli` com a biblioteca `dd-trace-php` instalada. O comportamento da aplicação está em `index.php`, que responde em `/` e `/ping`, envia métricas via DogStatsD e gera dados para observabilidade e testes de autoscaling.

## Passo a Passo Resumido da Solução

1. Suba a aplicação PHP de teste com os arquivos `Dockerfile`, `index.php` e `deployment.yaml`.
2. Instale ou atualize o Datadog no namespace `datadog` com Helm, usando o arquivo `datadog-values.yaml` para habilitar Agent, Cluster Agent e External Metrics Provider:

```bash
helm repo add datadog https://helm.datadoghq.com
helm repo update
helm upgrade --install datadog datadog/datadog -n datadog --create-namespace -f datadog-values.yaml
```
3. Crie o arquivo `datadog-secret.yaml` com `api-key` e `app-key`, para que o chart não precise manter credenciais em texto puro no `values`.
4. Crie o arquivo `datadogmetric.yaml` para publicar a métrica externa que será consultada pelo Cluster Agent.
5. Crie o arquivo `hpa.yaml` para que o `HorizontalPodAutoscaler` use a métrica externa do Datadog no scale do deployment `app-php`.
6. Mantenha esses arquivos na mesma pasta para facilitar a aplicação e a manutenção da solução.

Com os arquivos na mesma pasta, aplique tudo com:

```bash
kubectl apply -f deployment.yaml -f datadog-secret.yaml -f datadogmetric.yaml -f hpa.yaml
```

Os detalhes de configuração ficam centralizados em cada arquivo, então este README serve como guia rápido da sequência de implantação e validação.

## Comandos úteis

#### Configurar as chaves do Datadog com Secret
```bash
kubectl apply -f datadog-secret.yaml
```

Se o chart Datadog estiver instalado no namespace `datadog`, o arquivo `datadog-values.yaml` já está preparado para buscar o secret `datadog-secret`.

##### Para verificar o status do HPA, execute o comando:
```bash
kubectl describe hpa -n app-php
```

##### Para forçar a quantidade de réplicas:

```bash
kubectl scale deployment app-php -n app-php --replicas=1
```

#### Para verificar os logs do pod para erros do ddtrace

```bash
kubectl logs -n app-php -l app=app-php --tail=50 | grep -i "datadog\|ddtrace\|error"
```

#### Para verificar se o agente está recebendo traces

```bash
kubectl logs -n datadog -l app=datadog --tail=50 | grep -i "trace\|apm\|error"
```

#### Para simular uma aplicação enviando métricas
- Entre em um pod de teste
```bash
kubectl run statsd-test --image=alpine -it --rm -- sh
```
- Instale o netcat
```bash
apk add netcat-openbsd
```
- Envie uma métrica de teste para o agente Datadog
```bash
echo "test.metric:1|c" | nc -u -w0 datadog.datadog.svc.cluster.local 8125
``` 
- Ou crie o pod no namespace datadog e execute o comando anterior

```bash
kubectl run statsd-test \
--image=alpine \
-n datadog \
-it --rm -- sh
```
- Procure no log do Agent Datadog (eventos do pod de teste)
```bash
kubectl logs -n datadog -l app=datadog --tail=50 | grep -i "datadog_statsd-test_"
```

#### Verificar se o DogStatsD está com tráfego ativo
```bash
kubectl exec -it <agent-pod> -n datadog -- agent config | grep dogstatsd
```
- Verifique o resultado (true ou false):
deve estar --> dogstatsd_non_local_traffic: true

#### Para rodar um diagnóstico no agent
```bash
kubectl exec -it <agent-pod> -n datadog -- agent diagnose
```
#### Log do Agent em tempo real
```bash
kubectl logs -n datadog ds/datadog -c agent -f
```
#### Para rodar em loop a aplicação para testes
```bash
for i in {1..50}; do curl -s http://localhost:30080/ > /dev/null; done
```
