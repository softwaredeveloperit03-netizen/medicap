import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from  '@angular/router';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  results;
  reserve_sample=0;
  micro_analysis=0;
  chemical_analysis=0;
  total=0;
  units
  constructor(private service : DataAccessService,private router : Router) { }

  ngOnInit() {
    this.getProducts();
    this.getUnits();
  }
  getProducts(){
    this.service.get('common.php?type=getProducts').subscribe(response=>{
      this.results = response;
    })
  }
  getUnits(){
    this.service.get('common.php?type=getUnits').subscribe(response =>{
      this.units = response;
    });
  }
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  calulation(formData){
    this.total = +this.chemical_analysis +  +this.micro_analysis 
    this.total += +this.reserve_sample
    console.log(this.total)
  }
  
  saveReading(formData) {
    if (!formData.valid) {
      alertify.error('All fields are required');
      return;
    }
  this.service.post('qc/sampling/samplingqty.php?type=saveSampleQty',JSON.stringify(formData.value)).subscribe(response => {
    if(response['status'] == 'success') {
     alertify.success('Record Inserted Successfully');
     this.router.navigate(['/qc/sampling/finish/sample_quantity'])
     formData.resetForm();
    } else {
      alertify.error('Failed: An error occured, please try again!');
    }
  });
}
 
}
