
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-bmrlog',
  templateUrl: './bmrlog.component.html',
  styleUrls: ['./bmrlog.component.css']
})
export class BmrlogComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getPlans();
  }
  results;
  getPlans() {
    this.service.get('production/plan.php?type=get_comp_bmr_sp').subscribe(response => {
      this.results = response;
    
    }); 
  }

  selectedOrder;
  download(bmr_no)
  {
    this.service.open('production/plan.php?type=downloadReport&bmr_no='+bmr_no);
  }
}
