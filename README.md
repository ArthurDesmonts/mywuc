# **Mywuc: A wallet expenses viewer/controller**
## The objective of this app:

The objective of Mywic is to control your income better than your traditional bank app. I hope this is going to permit you to have a better vision of your money flows and permit you to invest where it counts the most.

## **Follow the development of the project through this chart**:

### [Trello-Febuary-Project](https://trello.com/b/utFHUF44/projet-de-fevrier)

### <ins>This is the class diagram followed for the entities:</ins>

```mermaid
classDiagram
    direction TB
    class User {
	    -int ID
	    -String name
	    -String firstName
	    -String mail
	    -String phone
	    -long walletID
	    +getID()
	    +getName()
	    +getFirstName()
	    +getMail()
	    +getPhone()
	    +getWalletID()
    }
    
    class Transaction {
	    -long ID
	    -float amount
	    -date date
	    -TransactionType type
	    +getAmount()
	    +getDate()
	    +getType()
    }

    class Wallet {
	    -long ID
	    -float sold
	    +getID()
	    +getSold()
    }

    class TransactionType {
        <<enumeration>>
        CREDIT
        DEBIT
    }

    User "1" -- "1" Wallet
    Wallet "1" -- "*" Transaction
    Transaction --> TransactionType
