import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css']
})
export class FormComponent implements OnInit {
isView = false;
checkListData;
selectedCheckList ;
  constructor(private service:DataAccessService) { }

  
  ngOnInit(): void {
    this.getchecklistnew()
  }

  // view(index){
  //   this.orders=selected
  // }

  getchecklistnew() {
      this.service.get('master/checklist.php?type=getMastercheckList').subscribe((response: any) => {
      this.checkListData = response;
    
    });
  }

  view(index)
  {
    this.isView =  true ;
    this.selectedCheckList = this.checkListData[index];

  }
}
