import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  isNew = false;
  selectedEntry;

  Oos = [];

  list;

  constructor(private service: DataAccessService, private router: Router) {

   }

  ngOnInit() {
    this.getOosData();
  }


  view(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  getOosData() {
    this.service.get('qc/oos.php?type=getOosData').subscribe(response => {
      this.list = JSON.parse(JSON.stringify(response));
    });
  }

    addVendorAgenda() {
      this.isNew = true;
    }

  close() {
    this.router.navigate(['/oos']);
  }

}
