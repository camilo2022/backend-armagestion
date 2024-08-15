# vericsv.py   Verificación de archivo .csv
#
#                                                 2023jun14-1434
#

import csv
import os
import sys
import http.cookiejar as cookiejar
import psycopg2
import yaml
from datetime import date, time, datetime
from decimal import Decimal

path_file =  sys.argv[1]

cookie_jar = cookiejar.CookieJar()
cookie = cookiejar.Cookie( version =0, name='fchsube', value='20230615-193302', port=None, port_specified=False, domain='.Armagestion', domain_specified=True, domain_initial_dot=False, path='/', path_specified=True, secure=False, expires=None, discard=True, comment=None, comment_url=None, rest=None)
def validar_archivo_csv22( entidad , archivo , totcampo ) :

   with open("/var/www/html/Armagestion/public/config.yaml" , "r") as f:
      config = yaml.safe_load( f )
 
   dbname = config["database"]["dbname"]
   dbhost = config["database"]["dbhost"]
   dbport = config["database"]["dbport"]
   dbuser = config["database"]["dbuser"]
   dbpasw = config["database"]["dbpasw"]
 
 
   conn = psycopg2.connect(database=dbname,
      user=dbuser, password=dbpasw,
      host=dbhost, port=dbport
   )
 
 
   conn.autocommit = True
   cursor = conn.cursor()
   print( entidad , " : " , archivo )
   if not os.path.isfile( archivo )  :
      print( "No existe!" )
      return False
  
   with open( archivo , newline = '' , encoding='utf-8' ) as file:
      reader = csv.reader( file )
      arre = [ row for row in reader ]
      stu = arre[0][0]
      stu = stu.replace(',', '').replace("'","")
      deli = stu.count(';')
      encabezado = stu.split(';')
      subidos = 0
      fallidos = 0
      error = ''
      tipos_validos = [
         str,         # cuenta 
         str,         # estado_gestion
         str,        # fecha_inicio_gestion
         str,        # hora_inicio_gestion 
         str,        # fecha_fin_gestion
         str,        # hora_fin_gestion
         str,         # duracion_gestion_(min)
         str,         # documento_cliente
         str,         # cliente
         str,         # documento_ejecutivo 
         str,         # ejecutivo
         str,         # tipificacion
         str,        # gestion_efectiva
         str,         # estrategia
         str,         # homologacion_gestion
         str,         # motivo_no_pago
         str,         # dato_contacto
         str,         # tipo_de_pago
         str,        # fecha_pago
         str,     # valor_pago 
         str,         # observacion
         str,         # aliado
         str,         # asignacion
         str,         # campana
         str,         # referencia_pago
         str,         # codigo_cliente  
         str,         # cantidad_productos
         str,         # tipo_cartera
         str,        # prospecto
         str,         # segmento_cuenta 
         str,         # ciclo
         str,         # numero_obligaciones
         str,         # dias_mora
         str,         # edad_mora
         str,         # foco_aliado
         str,         # tipo_producto
         str,     # saldo_inicial
         str,     # descuento
         str,        # fehca_inicio_descuento
         str,        # fecha_fin_descuento  
         str,         # min
         str,         # direccion
         str,         # region
         str,         # departamento
         str,         # dia_de_la_semana
         str          # numero_de_la_hora
      ]     
      if deli == 0 and totcampo > 1 :
         print( "EL DELIMITADOR DE CAMPO DEBE SER PUNTO Y COMA (;)" )
         return False
      else :
         campo = stu.split(';')
         if len(campo)!=totcampo :
            print( 'NUMERO DE COLUMNAS ERRONEO. SE REQUIEREN: '+str(totcampo)+', SE REPORTAN: '+str(len(campo))+'.' )
            return False
         else:
            for row in range(1,len(arre)):
               if len(arre[row]) == 0:
                  error += 'FILA: '+str(row+1)+' | ERROR: NO HAY DATOS. \n\n'
                  fallidos+=1
               else:
                  if len(arre[row]) > 0:
                     arre[row] = arre[row][0].replace("; ;",";;").replace(";;","; ;").split(';')                 
                  if len(arre[row]) < totcampo or len(arre[row]) > totcampo:  
                     error += 'FILA: '+str(row+1)+' | ERROR: SE REQUIEREN '+str(totcampo)+' COLUMNAS, SE REPORTAN: '+str(len(arre[row]))+' COLUMNAS. \n\n'
                     fallidos+=1
                  else:
                     insert = 'INSERT INTO mejor_gestion ( "cuenta", "estado_gestion", "fecha_inicio_gestion", "hora_inicio_gestion", "fecha_fin_gestion", "hora_fin_gestion", "duracion_gestion_min", "documento_cliente", "cliente", "documento_ejecutivo", "ejecutivo", "tipificacion", "gestion_efectiva", "estrategia", "homologacion_gestion", "motivo_no_pago", "dato_contacto", "tipo_de_pago", "fecha_pago", "valor_pago", "observacion", "aliado", "asignacion", "campana", "referencia_pago", "codigo_cliente", "cantidad_productos", "tipo_cartera", "prospecto", "segmento_cuenta", "ciclo", "numero_obligaciones", "dias_mora", "edad_mora", "foco_aliado", "tipo_producto", "saldo_inicial", "descuento", "fehca_inicio_descuento", "fecha_fin_descuento", "min", "direccion", "region", "departamento", "dia_de_la_semana", "numero_de_la_hora") values ('
                     bol = True
                     for col in range(len(arre[row])):
                        # print(type(arre[row][col]) is tipos_validos[col])
                        # return False

                        arre[row][col] = arre[row][col].strip().replace('ñ','n').replace('á','a').replace('é','e').replace('í','i').replace('ó','o').replace('ú','u')
                        if arre[row][col] == '' or arre[row][col] is None:
                           bol = False
                           error += 'FILA: '+str(row+1)+' | COLUMNA: '+str(col+1)+' | ERROR: DATO NULL. \n'
                        elif type(arre[row][col]) is tipos_validos[col]:
                           if tipos_validos[col] is Decimal or tipos_validos[col] is int:
                              insert+= arre[row][col]+','
                           else:
                              insert+= "'"+arre[row][col]+"',"
                        else:
                           bol = False
                           error += 'FILA: '+str(row+1)+' | COLUMNA: '+str(col+1)+' | ERROR: TIPO DE DATO INVALIDO. \n'
                     if bol:
                        subidos+=1
                        insert+=");"
                        insert = insert.replace(",);",");")
                        # print(insert)
                        # return False
                        sql1 = insert
                        cursor.execute( sql1 )
                        error+= 'FILA: '+str(row+1)+' SUBIDA CON EXITO. \n\n'
                     else:
                        error+= '\n'
                        fallidos+=1

            ruta_errores = "/var/www/html/Armagestion/storage/app/errores_mejor_gestion.txt"
            with open(ruta_errores, "w", encoding="utf-8") as file:
             file.write(error)

      print( "Registros :" , subidos )
      print( "Fallidos :" , fallidos )
      print(error)

   conn.commit()
   conn.close()

if __name__ == '__main__' :
    validar_archivo_csv22('Prueba','/var/www/html/Armagestion/storage/app/'+path_file , 46 )
