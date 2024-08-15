# probalca.py     https://github.com/metalheah2/servidor_ssh_python/blob/main/Servidor_Python_Paramiko.py
#                 ejecucion local    2023.mzo.08
#
import socket
import sys

import sys

progrash = sys.argv[1]
#infgeve  = sys.argv[2]
#infnge1  = sys.argv[3]
#fecnge1  = sys.argv[4]
#infnge2  = sys.argv[5]
#fecnge2  = sys.argv[6]
#infnge3  = sys.argv[7]
#fecnge3  = sys.argv[8]

print( progrash )

# Create a TCP/IP socket
sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

# Connect the socket to the port where the server is listening
#server_address = ('172.16.10.81', 10000)
server_address = ('172.16.10.107', 21031)
print('connecting to {} port {}'.format(*server_address))
sock.connect(server_address)

try:

    # Send data
    message = b'This is the message.  It will be repeated.'
#    cad = progrash + " " + infgeve + " " + infnge1 + " " + fecnge1 + " " + infnge2 + " " + fecnge2 + " "+ infnge3 + " " + fecnge3
    cad = progrash
    message = bytes( cad , 'utf-8')

    print('sending {!r}'.format(message))
    sock.sendall(message)

    # Look for the response
    amount_received = 0
    amount_expected = len(message)

    while amount_received < amount_expected:
        data = sock.recv(16)
        amount_received += len(data)
        print('received {!r}'.format(data))

finally:
    print('closing socket')
    sock.close()
