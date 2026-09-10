import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-opening-stock',
  templateUrl: './opening-stock.component.html',
  styleUrls: ['./opening-stock.component.css']
})
export class OpeningStockComponent implements OnInit {

  results;
  products;
  units;
  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
    this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
    this.getProducts();
  }

  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  }
  
  saveOpeningStock(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp =  data.value;
    temp['qty_unit'] = "Nos";


    this.service.post('dispatch/opening.php?type=saveStock', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Saved Successfully');
        this.router.navigate(['/dispatch']);

      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }


}
