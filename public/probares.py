#!/usr/bin/env python
import socket
import sys
import os

# Create a TCP/IP socket
sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

# Bind the socket to the port
#server_address = ('172.16.10.81', 10000)
server_address = ( '172.16.10.107' , 21031 )
print('starting up on {} port {}'.format(*server_address))
sock.bind(server_address)
ruta = '/var/www/html/Armagestion/public/'
# Listen for incoming connections
sock.listen(1)

while True:
    # Wait for a connection
    print('waiting for a connection')
    connection, client_address = sock.accept()
    try:
        print('connection from', client_address)

        # Receive the data in small chunks and retransmit it
        while True:
            data = connection.recv(16)
            progrash = data.decode()
            prograco = "conv"+  progrash[ 4 : len( progrash ) ]
            p1       = 'sh ' +  ruta + progrash
#            progrash = 'sh ' .  progrash
            print('received {!r}'.format(data))
            if data:
                print('sending data back to the client')
#                os.system('sh traeAdmin.sh')
                print( p1 )
                os.system( p1 )
                connection.sendall(data)
            else:
                print('no data from', client_address)
                break

    finally:
        # Clean up the connection
        connection.close()
