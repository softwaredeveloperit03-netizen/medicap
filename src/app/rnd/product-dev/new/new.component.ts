import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  results;
  isView=false;
  requirement;
  Development_For:any
    productList: Object;
 
  constructor(private service:DataAccessService,private router :Router) { 
   

  }

  ngOnInit() {
    this.getDosage();
    this.getProducts()
  }
  getDosage(){
    this.service.get('common.php?type=getDosages').subscribe(response=>{
      this.results=response;
    })
  }
  getProducts() {
    this.service.get('master/product.php?type=getBrandProductsLog').subscribe(response => {
      this.productList = response;
    });
  }

  
  save(data){
    console.log(data.value);
    if (!data.valid) {
      alertify.error('All fields are required');

      return;
    }
    this.service.post('rnd/product.php?type=saveProduct',JSON.stringify(data.value)).subscribe(response=>{
      if(response['status'] == 'success'){
        alertify.success('Data Updated Successfully!');
       this.router.navigate(['/rnd/product-dev'])
      }else{
        alertify.error('An Error Occured, Please try again!');
      }
    });



  }

}
