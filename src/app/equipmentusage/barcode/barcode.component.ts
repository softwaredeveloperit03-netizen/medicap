import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-barcode',
  templateUrl: './barcode.component.html',
  styleUrls: ['./barcode.component.css']
})
export class BarcodeComponent implements OnInit {

  constructor(private service:DataAccessService,private router : Router) { }

  ngOnInit(): void {
this.getDetails();
  }
  data;
  getDetails(){
    this.service.get('common.php?type=get_Equipments&depart='+localStorage.getItem('department')).subscribe((response:any) => {
      this.data = response;
    });
  }


 PrintBarcode(equipmentCode: string) {
  // Code39 requires * at start & end
  const barcodeValue = `*${equipmentCode}*`;

  const win = window.open('', '_blank');
  if (win) {
    win.document.write(`
      <html>
        <head>
          <!-- Barcode font -->
          <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet">
          <style>
            .barcode {
              font-family: 'Libre Barcode 39', cursive;
              font-size: 70px;
              text-align: center;
              margin-top: 50px;
            }
            .text {
              text-align: center;
              font-size: 16px;
              margin-top: 10px;
            }
          </style>
        </head>
        <body>
          <div class="barcode">${barcodeValue}</div>
          
          <script>window.print();</script>
        </body>
      </html>
    `);
    win.document.close();
  }
}


}

