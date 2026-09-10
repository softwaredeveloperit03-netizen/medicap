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

  results;
  selectedResult = [];

  frequency = 'once in a week';
  constructor(private service: DataAccessService,private router:Router){ }

  ngOnInit(): void {
    this.getDepartments();
  }
  getDepartments(){
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.results = response; 
    });
  }

  getDetails(index){
    index = index - 1;
    if(index !== -1){
      this.selectedResult = this.results[index];
    }
    
  }


  save(data){
    this.service.post('qc/water.php?type=savePoint',JSON.stringify(data.value)).subscribe(response=>{
      alertify.success("submitted succesfully");
      data.reset();
     
    });
  
  }














  

  // water_type;
  // add_water_type() {
  //   this.service.get('admin/housekeeping.php?type=add_floornumber&water_type=' + this.water_type).subscribe(response => {
  //     if (response['status'] == 'success') {
  //       this.getwater();
  //       this.add_water = false;

  //       alertify.success('Dust bin no saved successfully');

  //     } else {
  //       alertify.error(response['status']);
  //     }
  //   });

  // }


  // add_water;
  // add_waterType(value) {
  //   if (value == 'Add New') {
  //     this.add_water = '';
  //     this.add_water = true;
  //   }
  // }


  // waterTYPE;
  // getwater() {
  //   this.service.get('admin/housekeeping.php?type=get_floor_nums').subscribe(response => {
  //     this.waterTYPE = response;
  //   });
  // }




}
