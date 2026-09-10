import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-inspectionfhyd',
  templateUrl: './inspectionfhyd.component.html',
  styleUrls: ['./inspectionfhyd.component.css']
})
export class InspectionfhydComponent implements OnInit {
  //stpinspectionfire;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    //this.getstpinspectionfire();
  }


  // getstpinspectionfire(){
  //   this.service.get('common.php?type=getstpinspectionfire').subscribe(response =>{
  //       this.stpinspectionfire =response
  //     });
  // }


  submitinspectionfire(data) {
    let temp = data.value;
    console.log(temp);
    //this.service.post('',JSON.stringify(temp)).subscribe(response => {
    //  console.log('Saved Successfully');
    //})
  }

}
