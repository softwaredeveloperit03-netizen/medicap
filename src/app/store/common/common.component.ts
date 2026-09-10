import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
 declare let alertify;
@Component({
  selector: 'app-common',
  templateUrl: './common.component.html',
  styleUrls: ['./common.component.css']
})
export class CommonComponent implements OnInit {



  Material_type = '';

  constructor(private service: DataAccessService) { }
  results;
  isView = false;
  selectedResult;
  ngOnInit(): void {
    //this.getmaindata();
  }



  getmaindata(){
    this.service.get('store/opening.php?type=getCommonlog&Material_type='+this.Material_type).subscribe(response => {
      this.results = response;
     }); 
  }

  view(index){

    this.selectedResult = this.results[index];
    this.isView = true;
  }
  download()
  {
    this.service.open('store/opening.php?type=getCommonlogPDF&Material_type='+this.Material_type+'&arNo='+this.selectedResult['ar_no']+'&remQty='+this.selectedResult['Issue'])
  }

}
