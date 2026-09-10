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

  categoryList;
  product;
  material_type;
  products;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.categoryList=[];  
    this.getData();
  }
  initCategory(){
    this.categoryList=[];  
  }
  getData() {
     
    this.service.get('production/additional_material.php?type=get_product_list').subscribe(response => {
      this.products = response;
    
    });
  }

  saveform(Form){
    if (!Form.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = Form.value;
    this.service.post('production/additional_material.php?type=saveAdditional', JSON.stringify(temp)) 
    .subscribe(response => {
      if (response['status'] === 'success') {       
        Form.resetForm();
        alertify.success("save successfully");
      } else {
        alertify.error('Please Try Again');
      }
      })
    }
}
