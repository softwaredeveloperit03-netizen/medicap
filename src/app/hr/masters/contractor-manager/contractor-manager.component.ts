import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-contractor-manager',
  templateUrl: './contractor-manager.component.html',
  styleUrls: ['./contractor-manager.component.css']
})
export class ContractorManagerComponent implements OnInit {

  contractors;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getContractors();
  }

  getContractors() {
    this.service.get('hrDepartment.php?type=getContractors').subscribe(response => {
      this.contractors = response;
    });
  }

}
