import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-withdrawal',
  templateUrl: './withdrawal.component.html',
  styleUrls: ['./withdrawal.component.css']
})
export class WithdrawalComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  constructor(private service:DataAccessService,private router:Router) { }


  ngOnInit() {
    this.getPendingTechnicalInfos();
  }

  getPendingTechnicalInfos(){
    this.service.get('production/technical.php?type=getPendingTechnicalInfos').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  withdrawal(){
    this.service.post('production/technical.php?type=withdrawSample&id=' + this.selectedResult['id'] + '&sample_qty=' + this.selectedResult['sample_qty'] + '&unit=' + this.selectedResult['unit'],JSON.stringify(this.results)).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Sample Qty Withdrawal Successfully!');
        this.router.navigate(['/technical-info']);
      }else{
        alert('Failed an error occured,Please try again!');
      }
    });
  }

}
