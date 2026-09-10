import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  isView = false;
  results;
  clients;
  selectedResult = [];
  status ='';
  legal_name ='';
  items;
  
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getTransportersLog();
    this.getclientlist();
  }
  getTransportersLog(){
    this.service.get('marketing/transporter.php?type=getTransportersLog').subscribe((response: any) => {
      this.results = response;
      // this.filterItem();
    });

  }
  getclientlist() {
    this.service.get('marketing/po.php?type=getClients').subscribe((response:any) => {
    this.clients = response;
    });
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  // filterItem() {
  //   this.items = [];
  //   for (let i = 0; i < this.results.length; i++) {
  //     let material = this.results[i];
  //     if (material['legal_name'].toUpperCase().includes(this.legal_name.toUpperCase())&&material['status'].toUpperCase().includes(this.status.toUpperCase())) {
  //       this.items[this.items.length] = material;
  //     }
  //   }
  // }
    
}
