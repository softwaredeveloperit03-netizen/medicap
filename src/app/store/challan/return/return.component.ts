import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-return',
  templateUrl: './return.component.html',
  styleUrls: ['./return.component.css']
})
export class ReturnComponent implements OnInit {
results: any[] = [];
  constructor(private service:DataAccessService ) { }

  ngOnInit(): void {
    this.getReturnableList();
  }

  getReturnableList(){
    this.service.get('store/challan.php?type=getReturnableList').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    })
  }

  isView = false;
  selectedResult;
  view(index){
          this.selectedResult = this.results[index];
          this.isView = true;
          console.log(this.selectedResult);
  }
}
