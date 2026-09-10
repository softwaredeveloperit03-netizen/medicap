import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
import * as FileSaver from 'file-saver';

declare let alertify;
@Component({
  selector: 'app-prodforcast',
  templateUrl: './prodforcast.component.html',
  styleUrls: ['./prodforcast.component.css']
})
export class ProdforcastComponent implements OnInit {

  constructor(private service: DataAccessService) {
    
  }
  plant_id;
  plant_type;
  ngOnInit(): void {
    this.getproduct();
 
  }
  results;
    getproduct() {
    this.service
      .get(
        'master/product.php?type=getProductsLogMRPLOG'
      )
      .subscribe((response) => {
        this.results = response;

      });
  }
}
