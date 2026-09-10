import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  constructor(private service:DataAccessService,private router: Router) { }

  ngOnInit(): void {
  }
  save(data){
    if(data.valid)
    this.service.post('qc/indicator.php?type=saveIndicator',JSON.stringify(data.value)).subscribe(response=>{
     this.router.navigate(['/master/indicator/log'])
      alertify.success("submitted succesfully");
      data.reset();
    })
    else{
      alertify.error("enter valid data")
    }
  }
 }




