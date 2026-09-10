import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe]
})
export class NewComponent implements OnInit {

  products;
  product_code;
  curr_date;

  constructor(private service : DataAccessService, private datePipe: DatePipe, private router: Router) {
    this.curr_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
   }

  ngOnInit(): void {
      this.curr_date = Date.now();
  }

  getProductsByType(type){
    this.service.get('production/product.php?type=getProductsByType&product_type='+type).subscribe(response => {
      this.products = response;
    });
  }

  getProductCode(val){
    console.log(val,this.products[val]);
    this.product_code = this.products[val].product_code;
  }

  save(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }  
    this.service.post('dispatch/opening.php?type=saveStock', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        this.router.navigate(['/master/blister'])
        alertify.success('Form has been saved successfully.');
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }
}
