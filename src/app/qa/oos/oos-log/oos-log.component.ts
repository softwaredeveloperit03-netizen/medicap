import { Component, OnInit } from '@angular/core';
import { FormBuilder } from '@angular/forms';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-oos-log',
  templateUrl: './oos-log.component.html',
  styleUrls: ['./oos-log.component.css']
})
export class OosLogComponent implements OnInit {

  isView = false;
  isNew = false;
  selectedEntry;

  Oos = [];

  list;

  constructor(private service: DataAccessService, private router: Router, private fb: FormBuilder) {

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



