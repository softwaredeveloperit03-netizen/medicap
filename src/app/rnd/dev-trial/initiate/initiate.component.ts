import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-initiate',
  templateUrl: './initiate.component.html',
  styleUrls: ['./initiate.component.css']
})
export class InitiateComponent implements OnInit {
  isShowProduct=false;
  products;
  selectedProduct=[];

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getProducts();
  }
  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  } 
  getProdDetails(index) {
    index = index - 1;
    this.selectedProduct = this.products[index];
    this.isShowProduct = true;
  }
  save(data){
    if(data.valid)
    this.service.post('rnd/devtrial.php?type=saveInitiateTrial',JSON.stringify(data.value)).subscribe(response=>{
        alertify.success("data save successfully");
        data.reset();
        this.router.navigate(['/dev-trial'])
    });
    else{
      alertify.error('All fields are required');
    }

  }
  
}
