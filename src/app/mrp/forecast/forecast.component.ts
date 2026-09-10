import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-forecast',
  templateUrl: './forecast.component.html',
  styleUrls: ['./forecast.component.css']
})
export class ForecastComponent implements OnInit {


  pendingpo;
  selectresult = [];
  isView=false;


  constructor(private service:DataAccessService  , private router: Router) {

  }
      
  yearOptions=[]
  ngOnInit() {
    this.getPOsLog();
        
   }

 
isPrepared = false;


getPOsLog() {
  this.service.get('mrp/mrp.php?type=getproductsYearly').subscribe(response => {   
      this.pendingpo = response;   
  });
 
}







selectedProducts: any[] = [];

onCheckboxChange(product: any) {
  if (product.selected) {
    if (!this.selectedProducts.includes(product)) {
      this.selectedProducts.push(product);
    }
  } else {
    this.selectedProducts = this.selectedProducts.filter(p => p !== product);
  }
  console.log("Selected:", this.selectedProducts);
}
merrgedData=[];
proceed() {
  this.merrgedData=[];
  const grouped: any = {};

  // Group products by genericProductCode
  this.selectedProducts.forEach(product => {
    const key = product.genericProductCode;

    if (!grouped[key]) {
      grouped[key] = {
        generic_name: product.generic_name,
        genericProductCode: product.genericProductCode,
        productDetails: [],
        total_qty: 0
      };
    }

    // Add product details
    grouped[key].productDetails.push({
      product_name: product.product_name,
      product_code: product.product_code,
      order_qty: Number(product.order_qty) || 0
    });

    // Sum qty
    grouped[key].total_qty += Number(product.order_qty) || 0;
  });

  // Convert to array
  const mergedArray = Object.values(grouped);
  this.merrgedData=mergedArray

  console.log("Final merged array:", mergedArray);

  /**
   * mergedArray structure:
   * [
   *   {
   *     generic_name: "Paracetamol",
   *     genericProductCode: "GEN001",
   *     total_qty: 150,
   *     productDetails: [
   *       { product_name: "PCM 500", product_code: "P001", order_qty: 100 },
   *       { product_name: "PCM 650", product_code: "P002", order_qty: 50 }
   *     ]
   *   },
   *   { ... }
   * ]
   */
}


Save(){


     this.service.post('mrp/mrp.php?type=save_yearly_forecast_qty', JSON.stringify(this.merrgedData)).subscribe(response => {
        if (response['status'] == 'success') {
         
          alertify.success(this.service.t('common.savedSuccess'));
          this.merrgedData=[];
          // this.router.navigate(['/planning/plan']);
        } else {
          alertify.error('An error occured, Please try again');
        }
      });
}
}
