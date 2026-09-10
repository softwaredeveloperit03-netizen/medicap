import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-alternet',
  templateUrl: './alternet.component.html',
  styleUrls: ['./alternet.component.css']
})
export class AlternetComponent implements OnInit {
  selectedResult=[];
  results;
  isView = false;
  constructor(private service: DataAccessService, private router: Router) { }
  


  ngOnInit(): void {
    this.getAlternate();
  }
  getAlternate(){
    this.service.get('hr/leaveForm.php?type=getAlternate').subscribe(response => {
      this.results = response;
    })
  }
  
    view(index) {
      this.selectedResult = this.results[index];
      this.isView = true;
    }

}
