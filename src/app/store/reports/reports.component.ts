import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-reports',
  templateUrl: './reports.component.html',
  styleUrls: ['./reports.component.css']
})
export class ReportsComponent implements OnInit {
  stocks;
  Suppliers;
  Statements;
raw_report: any;
packing_report: any;
  constructor(private service: DataAccessService, private router: Router) { }
   ngOnInit(): void {
    this.getGRNLog();
   
    this.getPackingMaterialSupplierList();
    this.getItemStockStatement();
   }
  getGRNLog() {
    this.service.get('store/rawreport.php?type=getGRNLog').subscribe(response => {
      this.stocks = response;
    });
  }
 getPackingMaterialSupplierList() {
    this.service.get('store/rawreport.php?type=getPackingMaterialSupplierList').subscribe(response => {
      this.Suppliers = response;
    });
  }
  getItemStockStatement() {
    this.service.get('store/rawreport.php?type=getItemStockStatement').subscribe(response => {
      this.Statements = response;
    });
  }
  download(){
    this.service.open('store/reports.php?type=GRNRegister')
    }
  download2(){
    this.service.open('store/reports.php?type=PartlyOrderReceived')
    }
 
  download4(){
    this.service.open('store/reports.php?type=ItemStockStatement')
    }
  download5(){
    this.service.open('store/reports.php?type=ItemStockTaking')
    }
  download6(){
    this.service.open('store/reports.php?type=ListofBrandNameandGenericName')
    }
   
  download10(){
    this.service.open('store/reports.php?type=PackingMaterialSupplierList')
    }
  download11(){
     this.service.open('store/reports.php?type=UnTestedGRNReport')
    }
  download400(){
      this.service.open('store/reports.php?type=CategoryItemwisePMStock')
      }
      download309(){
        this.service.open('store/reports.php?type=RMStockStatement')
        }
  download13(){
    this.service.open('store/reports.php?type=NonMovableItems')
    }
  download14(){
    this.service.open('store/reports.php?type=RMConsumptionSummary')
    }
  download16(){
     this.service.open('store/reports.php?type=ItemReOrderList')
    }
    download388(){
      this.service.open('store/reports.php?type=TRNoERNOWiseStockStatement')
     }
  
  download177(){
    this.service.open('store/reports.php?type=MaterialwiseGRNRegister')
    }
  
  download19(){
    this.service.open('store/reports.php?type=ShortageMemo')
    }
  download22(){
    this.service.open('store/preport.php?type=GRNRegister')
     }
  download23(){
    this.service.open('store/preport.php?type=PartlyOrderReceived')
     } 
  download24(){
    this.service.open('store/preport.php?type=ItemwisePurchase')
     } 
  download25(){
    this.service.open('store/preport.php?type=ItemStockStatement')
    }
  download26(){
    this.service.open('store/preport.php?type=ItemStockTaking')
    }
  download27(){
    this.service.open('store/preport.php?type=ListofBrandnameandGenericName')
  } 
  download28(){
   this.service.open('store/preport.php?type=POofLastFiveYears')
   }
  download29(){
    this.service.open('store/preport.php?type=StockMovementStatement')
    }  
  download30(){
      this.service.open('store/preport.php?type=PackingMaterialSupplierList')
    }
  download31(){
      this.service.open('store/preport.php?type=UnTestedGRNReport')
    }
   download32(){
      this.service.open('store/preport.php?type=PurchaseOrderReconcillation')
    }
  download33(){
      this.service.open('store/preport.php?type=NonMovableItems')
    } 
  download34(){
      this.service.open('store/preport.php?type=RMConsumptionSummary')
    }
  download35(){
    this.service.open('store/preport.php?type=ItemReOrderList')
    } 
  download36(){
    this.service.open('store/preport.php?type=PurchaseListingbyEntryDate')
    }
  download37(){
    this.service.open('store/preport.php?type=MaterialwiseGRNRegister')
      }  
  download38(){
    this.service.open('store/preport.php?type=TRNoERNOWiseStockStatement')
    }
  download39(){
    this.service.open('store/preport.php?type=RMStockStatement')
      } 
  download40(){
    this.service.open('store/preport.php?type=CategoryItemwisePMStock')
     } 
  download41(){
    this.service.open('store/preport.php?type=RMPuchaseQuantity')
    }     
  }
