##RepeatedXOR

This is a challenge from PicoCTF 2014. We are given a hexa-encoded text which has been encripted with repeated XOR. 

For us to solve this challenge, it is necessary to know that repeating XOR cipher is basically a Vigenere cipher. Vigenere cipher is an encryption mechanism similar to simple Substitution cipher. We pick a key of some length shorter than the length of the plaintext (the key must not be an actualy English word found in dictionary as it would be very easy to bruteforce!). Then, starting from the left, we perform shifts (you can do XOR too) for each character in the plaintext with its corresponding character in the key. For example, if the plaintext were 'I HATE CHICKEN' and the key 'AXR', we do the following:
```
| I |   | H | A | T | E |   | C | H | I | C | K | E | N |
| A | X | R | A | X | R | A | X | R | A | X | R | A | X |   perform shift (or XOR) for each pair of characters (bytes)
```
Note that the key repeats because it is shorter than the plaintext. What is important is that when we perform encryption with a repeated key, we actually get repeated sequences of characters, which are most frequency the-key-length (or multiples of key-length) apart. The reason for this is that there are likely repeats in the plaintext itself.

####Properties on XOR
- XOR is used in encryption because of its special property

1. a XOR a = 0. i.e. anything XOR'ed with itself is 0
2. a XOR 0 = a. i.e. anything XOR'ed with 0 is itself
3. XOR is commutative and associative

```

With the facts in mind, assume p XOR k = c. Say p = plaintext, k = key, c = ciphertext. Note that length of key = length of plaintext.
Then c XOR k = (p XOR k) XOR k = p XOR (k XOR k) = p XOR 0 = p
```


The reason why I said repeated XOR is simply a Vigenere cipher is because this XOR property is the same as so-called "shift" in the Vigenere cipher. 

Back to the story, what Kasiski Examination does is to firstly calculate the spacings between each of repeated sequences, and for each length of spacing take all the factors of it (except for 1). For example, if the spacings were 8, 16, and 32, we have the sequence

```
| 2, 4, 8, | 2, 4, 8, 16 | 2, 4, 8, 16, 32 |
```

By sorting this sequence we see that the most-frequently occurring (the mode) and therefore the most likely key lengths are 2, 4, or 8. 

###Substitution cipher
From there, it is just a substitution-cipher hack. Assume that key length = x. Then we can split the ciphertext into small texts of length x. Now, the first byte of All the small texts are XOR'ed with the same, first key byte, the second byte the same second key byte etc. Then it becomes the matter of performing frequency analysis! 

The key here turns out to be
