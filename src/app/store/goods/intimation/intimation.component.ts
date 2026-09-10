import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify; 

@Component({
  selector: 'app-intimation',
  templateUrl: './intimation.component.html',
  styleUrls: ['./intimation.component.css']
})
export class IntimationComponent implements OnInit {
  from_date = '';
  to_date = '';
  today = '';
  results;
  goodsList=[];
  total_qty = 0;
  products;
  qty=0;
  quantity;
  constructor(private service: DataAccessService) {
  }

  ngOnInit(): void {
    this.service.observableProduct.subscribe(response =>{
      this.products = response;
    });
  }
 
  goods(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    
    let temp = data.value;
    this.goodsList[this.goodsList.length] = temp;
    this.total_qty =  +this.total_qty  +  +this.qty;  
    data.resetForm();
  }

  del(index) {
    // console.log(this.goodsList[index])
    this.quantity = this.goodsList[index];
    this.total_qty = +this.total_qty - + this.quantity['qty'];
    // console.log(this.total_qty)
    this.goodsList.splice(index,1);
  }

  saveGoods(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['details'] = this.goodsList;
    temp['total_qty'] = this.total_qty;
    this.service.post('store/goods.php?type=saveGoodsIntimation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Record Inserted successfully');
        this.goodsList=[''];
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
}