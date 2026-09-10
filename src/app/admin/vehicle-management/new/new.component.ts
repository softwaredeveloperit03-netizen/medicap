import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';

import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styles: [
  ]
})
export class NewComponent implements OnInit {


  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
  }

  save(data){
    if(data.valid)
    this.service.post('admin/vehicle_managment.php?type=saveVehiclManagement',JSON.stringify(data.value)).subscribe(response=>{
      alert("save successfully");
      data.reset();
      this.router.navigate(['/admin/vehicle-management']);
    });else{
      alert("all fields are required")
    }

  }

}
