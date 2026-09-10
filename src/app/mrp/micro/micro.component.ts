import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
import * as FileSaver from 'file-saver';
declare let alertify;

@Component({
  selector: 'app-micro',
  templateUrl: './micro.component.html',
  styleUrls: ['./micro.component.css'],
  providers: [DatePipe],
})
export class MicroComponent implements OnInit {
  isView = false;
  results;
  selectedResult = [];

  from_date = '';
  to_date = '';
  raw_materials: any = [];
  today = '';
  product_name = '';
  months = [
    { month: 'January', value: 'January' },
    { month: 'February', value: 'February' },
    { month: 'March', value: 'March' },
    { month: 'April', value: 'April' },
    { month: 'May', value: 'May' },
    { month: 'June', value: 'June' },
    { month: 'July', value: 'July' },
    { month: 'August', value: 'August' },
    { month: 'September', value: 'September' },
    { month: 'October', value: 'October' },
    { month: 'November', value: 'November' },
    { month: 'December', value: 'December' },
  ];
  showTailingBatches = false;
  constructor(
    private service: DataAccessService,
    private datePipe: DatePipe,
    private router: Router
  ) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }
  yearList: number[] = [];
  Years: number;
  ngOnInit() {
    const currentYear = new Date().getFullYear();
    for (let i = currentYear - 10; i <= currentYear + 10; i++) {
      this.yearList.push(i);
    }
  }

  getPlans() {
    this.service
      .get(
        'production/plan.php?type=getPlansForMicro&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.results = response;
      });
  }
  batches = [];
  isView1 = false;
  selectedbatches;
  plan_for_market;
  bfr_batch_size = 0;
  multi_packing;
  idps_unit;
  idps;

  getData() {
    this.service
      .get('planning/micro.php?type=getPlansForMicro&year=' + this.Years)
      .subscribe((response) => {
        this.results = response;
      });
  }
  datas: any = [];
  getQtyData(product_code, month) {
    this.isView = true;
    this.service
      .get(
        'planning/micro.php?type=getPlansForMicroMonthQty&month=' +
          month +
          '&year=' +
          this.Years +
          '&product_code=' +
          product_code
      )
      .subscribe((response) => {
        this.datas = response;
      });
  }

  downloadExcel(): void {
    const exportData = this.results.map((res, index) => ({
      'Sr. No.': index + 1,
      'Product Code': res.product_code,
      'Product Name': res.product_name,
      January: res.JanuaryQty,
      February: res.FebruaryQty,
      March: res.MarchQty,
      April: res.AprilQty,
      May: res.MayQty,
      June: res.JuneQty,
      July: res.JulyQty,
      August: res.AugustQty,
      September: res.SeptemberQty,
      October: res.OctoberQty,
      November: res.NovemberQty,
      December: res.DecemberQty,
    }));

    const worksheet = XLSX.utils.json_to_sheet(exportData);
    const workbook = {
      Sheets: { 'Monthly Plan': worksheet },
      SheetNames: ['Monthly Plan'],
    };
    const excelBuffer: any = XLSX.write(workbook, {
      bookType: 'xlsx',
      type: 'array',
    });

    const blob: Blob = new Blob([excelBuffer], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8',
    });

    FileSaver.saveAs(blob, `monthly-plan-${this.Years}.xlsx`);
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.plan_for_market = this.selectedResult['plan_for_market'];
    this.multi_packing = this.selectedResult['multi_packing'];
    this.batches = this.selectedResult['batches1'];
    this.idps_unit = this.selectedResult['pack_unit'];
    this.lowest_batch = this.selectedResult['lowest_batch'];
    this.idps = this.selectedResult['pack_size'];
    // this.bfr_batch_size=this.selectedResult['batch_size'];
    if (this.selectedResult['bom_type'] == 'Blending Batch') {
      this.showTailingBatches = true;
    } else {
      this.showTailingBatches = false;
    }
    this.isView = true;
  }

  packs: any = [];
  pack_size1;
  isView11(index) {
    this.selectedbatches = this.batches[index];
    this.bfr_batch_size = this.selectedbatches['batch_size'];
    this.isView = false;
    this.isView1 = true;

    this.getRmMatList();
    this.getPmMatList();

    console.log('object :>> ');

    this.setNoOfBatchesMacro(this.selectedbatches['planned_qty']);
  }

  getRmMatList() {
    this.service
      .get(
        'planning/raw.php?type=get_raw_materials_by_bfr_no&bfr_no=' +
          this.selectedbatches['bfr_no'] +
          '&batch_plan_id=' +
          this.selectedResult['batch_plan_id']
      )
      .subscribe((response) => {
        this.raw_materials = response['raw_materials'];
        this.calculateBatchQtyMacro();
      });
  }
  getPmMatList() {
    this.service
      .get(
        'planning/raw.php?type=get_pack_size_by_mfrno&unit_formula_dtl_id=' +
          this.selectedResult['unitformula_pm_dtl_id'] +
          '&bfr_no=' +
          this.selectedbatches['bfr_no'] +
          '&batch_plan_id=' +
          this.selectedResult['batch_plan_id']
      )
      .subscribe((response) => {
        this.packs = response;
        this.calculateBatchQtyMacro();
      });
  }
  planned_qty = 0;
  number_of_batches = 0;
  setNoOfBatchesMacro(planned_qty) {
    this.planned_qty = planned_qty;
    console.log('this.planned_qty :>> ', this.planned_qty);
    console.log('this.bfr_batch_size :>> ', this.bfr_batch_size);
    let bqty = Number(this.planned_qty) / Number(this.bfr_batch_size);
    console.log('object :>> 1');
    if (Number(bqty) > 0) {
      console.log('object :>> 2');
      let round = Math.round(bqty);
      this.number_of_batches = round;
      // this.calculateBatchQtyMacro();
    }
  }
  lowest_batch = 0;
  can_plan_qty;
  calculateBatchQtyMacro() {
    console.log('object :>> 3');
    console.log('this.raw_materials :>> ', this.raw_materials);

    let min_batch = [];
    console.log('this.raw_materials1 :>> ', this.raw_materials);
    for (let i = 0; i < this.raw_materials.length; i++) {
      console.log('object :>> 5');
      let qty = Number(
        this.raw_materials[i]['batch_qty'] * Number(this.number_of_batches)
      );
      this.raw_materials[i]['plan_qty'] = parseFloat(qty + '').toFixed(2);

      let can_plan_batches =
        Number(this.raw_materials[i]['avbl_stock']) /
        Number(this.raw_materials[i]['batch_qty']);
      let minBatch = Math.round(can_plan_batches);
      this.raw_materials[i]['can_plan_batches'] = minBatch;
      this.raw_materials[i]['shortage_qty'] = (
        Number(this.raw_materials[i]['avbl_stock']) -
        Number(this.raw_materials[i]['plan_qty'])
      ).toFixed(2);

      min_batch.push(minBatch);
      console.log('this.raw_materials2 :>> ', this.raw_materials);
    }
    console.log('object :>> 4');
    let temp = {};
    temp['packing_materials'] = [];
    for (let i = 0; i < this.packs.length; i++) {
      if (this.multi_packing == 'No') {
        this.packs[i]['batch_qtyqqqq'] =
          (Number(this.packs[i]['batch_formula_weight']) *
            Number(this.packs[i]['total_qty'])) /
          Number(this.packs[i]['batch_size']);

        this.packs[i]['plan'] = (
          Number(this.packs[i]['batch_qtyqqqq']) *
          Number(this.number_of_batches)
        ).toFixed(2);
      } else if (this.selectedResult['pack_multiple_countries'] == 'No') {
        let idps = 0;
        if (this.idps_unit == 'gm') {
          idps = Number(this.idps / 1000);
          console.log('idps=' + idps);
        }
        let qty0 = Number(this.bfr_batch_size) / Number(idps);
        console.log('qty0=' + qty0);
        let qty = Number(qty0) * Number(this.packs[i]['total_qty']);
        console.log('total_qty+' + this.packs[i]['total_qty']);
        console.log('qty=' + qty);
        this.packs[i]['plan'] = parseFloat(qty + '').toFixed(2);
        this.packs[i]['batch_qtyqqqq'] =
          (Number(this.packs[i]['batch_formula_weight']) *
            Number(this.packs[i]['total_qty'])) /
          Number(this.packs[i]['batch_size']);
      } else {
        //       for (let i = 0; i < this.country_configuration.length; i++) {
        //         const packs=this.country_configuration[i];
        //         for (let j = 0; j < packs['packs'].length; j++) {
        //           let idps = 0;
        //           if (this.country_configuration[i].idps_unit === 'gm') {
        //             idps = Number(this.country_configuration[i].packs1234 / 1000);
        //             console.log('idps=' + idps);
        //           } else {
        //             idps = Number(this.country_configuration[i].packs1234);
        //             console.log('idps=' + idps);
        //           }
        //           let qty0 = Number(this.country_configuration[i]['sale_qty']) / idps;
        //           console.log('sale_qty=' + this.country_configuration[i]['sale_qty']);
        //           console.log('qty0=' + qty0);
        //           let qty = qty0 * Number(packs['packs'][j]['total_qty']);
        //           console.log('total_qty=' + packs['packs'][j]['total_qty']);
        //           console.log('qty=' + qty);
        //           packs['packs'][j]['plan'] = parseFloat(qty + '').toFixed(2);
        //           console.log('plan=' + packs['packs'][j]['plan']);
        //           packs['packs'][j]['batch_qtyqqqq'] = (Number(this.country_configuration[i]['sale_qty']) * Number(packs['packs'][j]['total_qty'])) / Number(packs['packs'][j]['batch_size']);
        // console.log(packs['packs'][j]['batch_formula_weight']);
        // console.log('batch_qtyqqqq='+packs['packs'][j]['batch_qtyqqqq']);
        //         }
        //       }
      }

      this.packs[i]['can_plan'] =
        Number(this.packs[i]['batch_qtyqqqq']) * Number(this.lowest_batch);
      this.packs[i]['pm_overages'] = Number(this.packs[i]['pm_overages']);
      const balanceQty = Number(this.packs[i]['balance_qty']);
      const planQty = Number(this.packs[i]['plan']);
      const shortQty = balanceQty - planQty;
      this.packs[i]['short_qt'] = shortQty;

      temp['packing_materials'].push(this.packs[i]);
      console.log('this.packs[i] :>> ', this.packs[i]);
    }
    const min = Math.min(...min_batch);
    //  this.lowest_batch = min < 0 ? 0 : min;
    this.can_plan_qty = min < 0 ? 0 : min * this.bfr_batch_size;
    if (this.selectedResult['plan_type'] == 'Batch Wise') {
      this.planned_qty = this.bfr_batch_size * this.number_of_batches;
      console.log(this.planned_qty);
      console.log(this.bfr_batch_size);
      console.log(this.number_of_batches);
    }
  }

  download() {
    this.service.open(
      'production/bmr/plan.php?type=downloadPlans&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }

  update(status, id) {
    console.log(id);
    this.service
      .get(
        'production/plan.php?type=approveBatchPlanStatus&status=' +
          status +
          '&id=' +
          id
      )
      .subscribe((response) => {
        if (response['status']) {
          alertify.success('Status Updated Successfully');
          this.getPlans();
          this.isView = false;
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }
  saveBatchPlan(data, based_on) {
    let temp = this.selectedResult;
    // temp['data'] = this.selectedResult;
    // temp['bfr_no'] = this.selectedResult['bfr_no'];
    temp['raw_materials'] = this.raw_materials;
    temp['packing_materials'] = this.packs;

    console.log(temp);
    this.service
      .post('planning/raw.php?type=', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          data.resetForm();
          alertify.success(this.service.t('common.savedSuccess'));
          this.router.navigate(['/planning/production']);
        } else {
          alertify.error('An error occured, Please try again');
        }
      });
  }
}
