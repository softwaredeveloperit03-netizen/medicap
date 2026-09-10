import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class EditComponent implements OnInit {

  isView = false;
  results;
  selectedBalance = [];
  constructor(private service: DataAccessService) { }



  ngOnInit(): void {
    this.getVendors();
  }

  getVendors(){
    this.service.get('store/master_checklist.php?type=gatemaster_checklist').subscribe(response=>{
      this.results = response;
    });
  }

  view(index) {
    this.selectedBalance = this.results[index];
    this.isView = true;
  }
}

