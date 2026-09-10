import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-changemrphistory',
  templateUrl: './changemrphistory.component.html',
  styleUrls: ['./changemrphistory.component.css']
})
export class ChangemrphistoryComponent implements OnInit {
  results: any;

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getMrp()
  }

  getMrp() {
   
    this.service.get('master/mrp.php?type=getMrp').subscribe(response => {
      this.results = response;
      
    });
  }
  getSearchMrp(value) {
   
    this.service.get('master/mrp.php?type=getSearchMrp&value='+value).subscribe(response => {
      this.results = response;
      
    });
  }

}
