

## Comando úteis

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

#### Verificar se o DogStarsD está com tráfego ativo
```bash
kubectl exec -it <agent-pod> -n datadog -- agent config | grep dogstatsd
```
- Verifique o resultado (true ou false):
deve estar --> dogstatsd_non_local_traffic: true

#### Para rodar um diagnóstico no agent
```bash
kubectl exec -it <agent-pod> -n datadog -- agent diagnose
```
#### Log do Agnt em tempo real
```bash
kubectl logs -n datadog ds/datadog -c agent -f
```
#### Para rodar em loop a aplicação para testes
```bash
for i in {1..50}; do curl -s http://localhost:30080/ > /dev/null; done
```
