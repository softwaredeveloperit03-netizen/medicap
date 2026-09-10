import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getApprovedDayStores();
  }
 
  stores;
  getApprovedDayStores() {
      this.service.get('production/master.php?type=getApprovedDayStores').subscribe(response => {
        this.stores = response;
      });
  }

  routeTo(dayStoreName,dayStoreCode) {
    this.router.navigate(['/daystore/dayModule']);
    localStorage.setItem('dayStoreName',dayStoreName);
    localStorage.setItem('dayStoreCode',dayStoreCode);
  }

}
