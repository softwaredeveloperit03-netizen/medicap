import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service: DataAccessService,private router:Router) { }

  ngOnInit() {
   this.getEquipments();
   this.getProduct();
  }
 
  products;
  getProduct() {
    this.service.get('master/product.php?type=getProductApprovedProduct').subscribe((response) => {
        this.products = response;
      });
  }
  
  equipments;
  getEquipments() {
    this.service.get('common.php?type=getEquipments').subscribe((response) => {
        this.equipments = response;
    });
  }
  
  
 prodSpecific = 'NO';
  

  save(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }  
    this.service.post('master/blister.php?type=saveBlister', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        this.router.navigate(['/master/blister'])
        alertify.success('Form has been saved successfully.');
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  }

  





}
