import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  isView = false;
  products;
  dosages;
  selectedResult = [];
  product_type='';

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getProducts();
    this.getDosages();
  }

  getProducts() {
    this.service.get('production/batchformula.php?type=getBatchFormulas&product_type='+this.product_type).subscribe((response: any) => {
      this.products = response;
    });
  }

  getDosages(){
    this.service.get('common.php?type=getDosages').subscribe(response=>{
      this.dosages=response;
    });
  }


  view(index) {
    this.selectedResult = this.products[index];
    this.isView = true;
  }

  download(){
    this.service.open('production/batchformula.php?type=downloadBatchFormulas&product_type='+this.product_type)
  }

}
