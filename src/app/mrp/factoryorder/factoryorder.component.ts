import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-factoryorder',
  templateUrl: './factoryorder.component.html',
  styleUrls: ['./factoryorder.component.css']
})
export class FactoryorderComponent implements OnInit {


  pendingpo;
  selectresult = [];
  isView=false;


  constructor(private service:DataAccessService  , private router: Router) {

  }
     monthOptions = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
  ];
   dateOptions = [
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '10',
    '11',
    '12',
    '13',
    '14',
    '15',
    '16',
    '17',
    '18',
    '19',
    '20',
    '21',
    '22',
    '23',
    '24',
    '25',
    '26',
    '27',
    '28',
    '29',
    '30',
    '31'
  ];
  yearOptions=[]
  ngOnInit() {
    this.getPOsLog();
        this.initializeYearOptions();
   }
  initializeYearOptions() {
    const currentYear = new Date().getFullYear();
    for (let i = 0; i < 2; i++) {
      this.yearOptions.push((currentYear + i).toString());
    }
  }

ChangeLog(value) {
  if (value == 'Prepare') {
    this.getPOsLog1();
  } else {
    this.getPOsLog();  
  }
}
 
isPrepared = false;

getPOsLog1() {
  this.service.get('mrp/mrp.php?type=getPOsLogForSplits').subscribe(response => {
      this.pendingpo = response['prepares'];    
  });
  this.type='Prepared'
}
getPOsLog() {
  this.service.get('mrp/mrp.php?type=getPOsLogForSplits').subscribe(response => {   
      this.pendingpo = response['pending'];    
  });
  this.type='Pending'
}

  packing_materials=[];
  raw_materials=[];
  splits=[];
  bal_qty=0;
  type='Pending'
  view(index){
    this.selectresult = this.pendingpo[index];
    this.raw_materials = this.selectresult['raw_materials'];
    this.packing_materials = this.selectresult['packing_configuration'];
    this.isView = true;
    this.bal_qty = this.selectresult['qty_to_prepare'];
    this.splits = this.selectresult['splits'];
    if (this.splits.length > 0) {
      // Calculate the total Qty of all splits
      let totalSplitQty = 0;
      let totalSplitoutQty = 0;
      let totalSplitInQty = 0;
      for (let i = 0; i < this.splits.length; i++) {
        totalSplitQty += Number(this.splits[i].Qty);
        totalSplitoutQty += Number(this.splits[i].outQty);
        totalSplitInQty += Number(this.splits[i].InQty);
      }
      console.log('totalSplitQty :>> ', totalSplitQty);
      // Subtract the total from the initial balance quantity
      this.remainingQty = this.bal_qty - totalSplitInQty-totalSplitoutQty;
    } else {
      // If no splits, remainingQty equals the original balance
      this.remainingQty = this.bal_qty;
    }

  }
  isSPlit=false;
  Split(index){
    this.selectresult = this.pendingpo[index];
    this.raw_materials = this.selectresult['raw_materials'];
    this.packing_materials = this.selectresult['packing_configuration'];
    this.isSPlit = true;
        this.bal_qty = this.selectresult['qty_to_prepare'];
    this.splits = this.selectresult['splits'];
    if (this.splits.length > 0) {
      // Calculate the total Qty of all splits
      let totalSplitQty = 0;
      let totalSplitoutQty = 0;
      let totalSplitInQty = 0;
      for (let i = 0; i < this.splits.length; i++) {
        totalSplitQty += Number(this.splits[i].Qty);
        totalSplitoutQty += Number(this.splits[i].outQty);
        totalSplitInQty += Number(this.splits[i].InQty);
      }
      console.log('totalSplitQty :>> ', totalSplitQty);
      // Subtract the total from the initial balance quantity
      this.remainingQty = this.bal_qty - totalSplitInQty-totalSplitoutQty;
    } else {
      // If no splits, remainingQty equals the original balance
      this.remainingQty = this.bal_qty;
    }

  }


  downloadpo(doc_url) {
    doc_url = this.service.url + '../../upload/poentry/' + doc_url;
    window.open(doc_url, '_blank');
   }

   filtersFO =[];


   sendForReq( ) {

    this.filtersFO = this.pendingpo.filter(pending => pending.check).map(({ pid, order_no,product_code }) => ({ pid, order_no,product_code  }));

    console.log(this.filtersFO);

    if (this.filtersFO.length==0) {
      alert('Please Select Product!!!!!!');
      return;
    }

    let temp ={};
    temp['filtersFO'] = this.filtersFO;

    this.service.post('marketing/po.php?type=AcceptReqAnalysis', JSON.stringify(temp)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
         this.filtersFO = [];
        alert('Send For Requirment Analysis');
        this.getPOsLog();
      } else {
        alert('An error has occurred, please try again');
      }
    });




  }

OutinputQty=0;
inputQty=0;
remainingQty=0;
    addSplit(data: any) {
    const inputQuantity = Number(this.inputQty);
    // if (inputQuantity <= 0 || isNaN(inputQuantity)) {
    //   alert('Please enter a valid quantity greater than 0.');
    //   return;
    // }
    if (inputQuantity > this.remainingQty) {
      alert('Quantity is greater than the remaining balance quantity.');
      return;
    }
    // this.inputQty = 0;
    let temp=data.value;
    // temp['bal_qty']= this.remainingQty - inputQuantity-this.OutinputQty
    temp['bal_qty']= this.remainingQty - this.IninputQty-this.OutinputQty;
    temp['remainingQty'] -= inputQuantity;
    temp['product_name'] = this.selectresult['product_name'];
    temp['product_code'] = this.selectresult['product_code'];
    temp['order_no'] = this.selectresult['order_no'];
          if (this.inputQty == 0) {
          temp['BookedQty'] = this.IninputQty;
          console.log('hi :>> ', );
        } else {
          temp['BookedQty'] = this.IninputQty - inputQuantity;
          console.log('bi :>> ', );
        }
    this.service.post('mrp/mrp.php?type=saveWOEntryData', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Saved!!!');
        this.isView = false;
        this.isSPlit = false;
        this.getPOsLog();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }
IninputQty=0
onInputQtyChange() {
  this.inputQty = Math.max(0, Number(this.IninputQty) - Number(this.selectresult['avblStock']));
}









}
