import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-equipments-barcodes',
  templateUrl: './barcodes.component.html',
  styleUrls: ['./barcodes.component.css'],
})
export class BarcodesComponent implements OnInit {
  data: any[] = [];
  loading = false;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getDetails();
  }

  getDetails() {
    this.loading = true;
    this.service
      .get('common.php?type=get_Equipments&depart=' + localStorage.getItem('department'))
      .subscribe({
        next: (response: any) => {
          this.data = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        error: () => {
          this.data = [];
          this.loading = false;
        },
      });
  }

  PrintBarcode(equipmentCode: string) {
    if (!equipmentCode) {
      return;
    }
    const barcodeValue = `*${equipmentCode}*`;
    const win = window.open('', '_blank');
    if (win) {
      win.document.write(`
      <html>
        <head>
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
          <div class="text">${equipmentCode}</div>
          <script>window.print();</script>
        </body>
      </html>
    `);
      win.document.close();
    }
  }
}
