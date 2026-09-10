import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-acceptance',
  templateUrl: './acceptance.component.html',
  styleUrls: ['./acceptance.component.css']
})
export class AcceptanceComponent implements OnInit {
 

  isView= false;
  selectedResult= [];
  resignations;
  loading;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getData();
  }
  getData() {
   
    this.service.get('hr/resignation.php?type=get_resignation_by_emp_id').subscribe(response => {
      this.resignations = response;
    
    });
  }
  view(index) {
    this.selectedResult = this.resignations[index];
    this.isView = true;
  }
  show(){}
  
}