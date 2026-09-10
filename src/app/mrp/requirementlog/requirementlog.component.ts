import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-requirementlog',
  templateUrl: './requirementlog.component.html',
  styleUrls: ['./requirementlog.component.css']
})
export class RequirementlogComponent implements OnInit {

  constructor(private service:DataAccessService  , private router: Router) {

  }

  ngOnInit(): void {
    this.getmrp_raised_indnd_qty() 
  }
bookedQtys;
   getmrp_raised_indnd_qty() {
  this.service.get('mrp/mrp.php?type=getmrp_raised_indnd_qty').subscribe(response => {
      this.bookedQtys = response;    
       
  });
  
}
}
