import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  vendors;
  person;
  roleList =[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getVendors();
    this.getPerson();
  }

  getVendors(){
    this.service.get('purchase/vendor.php?type=getVendorLog').subscribe(response=>{
      this.vendors = response;
    });
  }

  getPerson(){
    this.service.get('employee.php?type=getQAPersons').subscribe(response=>{
      this.person = response;
    });
  }

  add(data){
    this.roleList[this.roleList.length]=data.value;
    data.reset();
  }

}
