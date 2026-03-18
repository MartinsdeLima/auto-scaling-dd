

### Comando úteis

## Para verificar o status do HPA, execute o comando:
```bash
kubectl describe hpa -n app-php
```

## Para forçar a quantidade de réplicas:

```bash
kubectl scale deployment app-php -n app-php --replicas=1
```

## Para verificar os logs do pod para erros do ddtrace

```bash
kubectl logs -n app-php -l app=app-php --tail=50 | grep -i "datadog\|ddtrace\|error"
```

## Para verificar se o agente está recebendo traces

```bash
kubectl logs -n datadog -l app=datadog --tail=50 | grep -i "trace\|apm\|error"
```

### Para simular uma aplicação enviando métricas (Deployment)
- Entre em no pod de teste
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
- Ou crie o pod no namespace datadog 

```bash
kubectl run statsd-test \
--image=alpine \
-n datadog \
-it --rm -- sh
```

- E então:
```bash
apk add netcat-openbsd
echo "test.metric:1|c" | nc -u -w0 datadog.datadog.svc.cluster.local 8125
```

- Procure
DogStatsD
=========
Metrics ...

E veja se os contadores estão aumentando.

## Verificar se o DogStarsD está com tráfego ativo
```bash
kubectl exec -it <agent-pod> -n datadog -- agent config | grep dogstatsd
```
- Verifique o resultado (true ou false):
dogstatsd_non_local_traffic: true

## PAra rodar um diagnóstico no agent
```bash
kubectl exec -it <agent-pod> -n datadog -- agent diagnose


## Para rodar em loop a aplicação para testes
```bash
for i in {1..50}; do curl -s http://localhost:30080/ > /dev/null; done
```

