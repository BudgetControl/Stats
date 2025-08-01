# TotalExpenses - Logiche di Controllo

## Panoramica

La classe `TotalExpenses` è un value object che gestisce specificatamente le spese all'interno del sistema di statistiche. Per garantire l'integrità dei dati e la correttezza delle operazioni, sono state implementate delle logiche di controllo rigorose.

## Logiche di Controllo Implementate

### 1. Validazione del Tipo di Entry

La classe `TotalExpenses` accetta solo entry di tipo "expenses". Il controllo viene effettuato in diversi modi:

#### Controllo Primario - Proprietà Type
```php
if (property_exists($entry, 'type') && $entry->type === 'expenses') {
    return true;
}
```

#### Controllo Secondario - Metodo getType()
```php
if (method_exists($entry, 'getType')) {
    return call_user_func([$entry, 'getType']) === 'expenses';
}
```

#### Controllo Fallback - Amount Negativo
Come fallback, se il tipo non è esplicito, vengono accettate le entry con amount negativo (caratteristica tipica delle spese):
```php
if (property_exists($entry, 'amount') && $entry->amount < 0) {
    return true;
}
```

### 2. Gestione degli Errori

Quando viene tentato di aggiungere un entry non valido, viene lanciata un'eccezione `InvalidArgumentException` con un messaggio dettagliato:

```php
throw new InvalidArgumentException(
    sprintf(
        'Only expense entries are allowed in TotalExpenses. Entry type "%s" is not allowed.',
        $entryType
    )
);
```

### 3. Metodi Protetti

#### validateExpenseEntry()
- **Scopo**: Valida che l'entry sia di tipo "expenses"
- **Comportamento**: Lancia eccezione se l'entry non è valido
- **Utilizzo**: Chiamato automaticamente nei metodi `sum()` e `substract()`

#### isExpenseEntry()
- **Scopo**: Determina se un entry è di tipo "expenses"
- **Strategia**: Utilizza una logica a cascata per massima compatibilità
- **Ritorno**: `true` se l'entry è valido, `false` altrimenti

#### getEntryType()
- **Scopo**: Estrae il tipo dell'entry per il reporting degli errori
- **Fallback**: Ritorna "unknown" se il tipo non può essere determinato

## Compatibilità

Le logiche di controllo sono progettate per essere compatibili con diverse implementazioni dell'`EntryInterface`:

1. **Proprietà pubbliche**: `$entry->type` e `$entry->amount`
2. **Metodi getter**: `getType()` e `getAmount()`
3. **Fallback logico**: Controllo basato sull'amount negativo

## Test Coverage

È stata implementata una suite di test completa che copre:

- ✅ Operazioni valide con entry di tipo "expenses"
- ✅ Reiezione di entry di tipo diverso ("incoming", "debit", etc.)
- ✅ Gestione di entry senza tipo esplicito
- ✅ Controllo fallback basato sull'amount
- ✅ Operazioni multiple e stato del value object

## Benefici

1. **Integrità dei Dati**: Garantisce che solo le spese vengano incluse nei totali
2. **Debugging Facilitato**: Messaggi di errore chiari e informativi
3. **Robustezza**: Gestione di diversi formati di entry
4. **Manutenibilità**: Codice ben documentato e testato

## Esempio di Utilizzo

```php
$totalExpenses = new TotalExpenses();

// ✅ Valido - Entry di tipo "expenses"
$expenseEntry = new Entry(['type' => 'expenses', 'amount' => -100.0]);
$totalExpenses->sum($expenseEntry);

// ❌ Invalido - Lancia InvalidArgumentException
$incomeEntry = new Entry(['type' => 'incoming', 'amount' => 200.0]);
$totalExpenses->sum($incomeEntry); // Eccezione!
```

Questo sistema di controlli garantisce che la classe `TotalExpenses` mantenga la sua responsabilità specifica di gestire esclusivamente le spese, contribuendo alla robustezza e affidabilità dell'intero sistema di statistiche.
