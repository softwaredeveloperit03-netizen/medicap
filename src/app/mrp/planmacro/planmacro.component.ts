import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
import * as FileSaver from 'file-saver';

declare let alertify;
@Component({
  selector: 'app-planmacro',
  templateUrl: './planmacro.component.html',
  styleUrls: ['./planmacro.component.css'],
})
export class PlanmacroComponent implements OnInit {
  pendingpo;
  selectresult = [];
  isView = false;
  plan_type = 'Total Qty Wise';

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.getPOsLog();
    this.getEquipments();
  }
  getTotalBatches(batches: any[]): number {
  if (!batches || batches.length === 0) {
    return 0;
  }
  return batches.reduce((sum, item) => sum + Number(item.total_batches || 0), 0);
}

  modalOpen: boolean = false;
wordOrderId=0;
stages=[];
  openModal(i) {
    
    this.wordOrderId = this.selectresult['batch_details'][i]['wordOrderId'];
    this.stages = this.selectresult['batch_details'][i]['stages'];
    this.modalOpen = true;
  }

  remainingQty;
Equipments;
  getEquipments() {
    this.service
      .get('master/equipment.php?type=getEquipments')
      .subscribe((response) => {
        this.Equipments = response;
      });
  }
  selectedEquipments=[]
  get_equipment_code_by_equipment(index) {
    this.selectedEquipments=this.Equipments[index - 1];
  }
  getPOsLog() {
    this.service
      .get('planning/raw.php?type=ZumaSplitPlanning')
      .subscribe((response) => {
        this.pendingpo = response;
      });
  }
  selectedItems: any[] = [];
  selectedQtySum: number = 0;

  updateSelection(): void {
    this.selectedItems = this.splits.filter((item) => item.selected);
    this.planned_qty = this.selectedItems.reduce(
      (sum, item) => sum + Number(item.Qty),
      0
    );
    console.log('Selected Items:', this.selectedItems);
    console.log('Selected Qty Sum:', this.planned_qty);
  }
  packing_materials = [];
  raw_materials = [];
  mfr_list = [];
  bal_qty;
  splits = [];
  view(index) {
    this.selectresult = this.pendingpo[index];
    this.bal_qty = this.selectresult['qty_to_prepare'];
    this.splits = this.selectresult['splits'];
    this.mfr_list = this.selectresult['mfr_records'];
    if (this.splits.length > 0) {
      // Calculate the total Qty of all splits
      let totalSplitQty = 0;
      for (let i = 0; i < this.splits.length; i++) {
        totalSplitQty += Number(this.splits[i].Qty);
      }
      console.log('totalSplitQty :>> ', totalSplitQty);
      // Subtract the total from the initial balance quantity
      this.remainingQty = this.bal_qty - totalSplitQty;
    } else {
      // If no splits, remainingQty equals the original balance
      this.remainingQty = this.bal_qty;
    }

    this.isView = true; // Open modal
    this.isViewMacro = false; // Open modal
  }
  isViewMacro=false;
  viewMicro(index) {
    this.selectresult = this.pendingpo[index];
    
    this.isViewMacro = true; // Open modal
    this.isView = false; // Open modal
  }
  inputQty;

  bfr_list = [];
  mfr_batch_size = 0;
  bfr_batch_size;
  unit_formula_id;
  getBfrList(index) {
    this.bfr_list = [];
    this.mfr_batch_size = this.mfr_list[index - 1]['batch_size'];
    this.bfr_list = this.mfr_list[index - 1]['bfr_records'];
    this.bfr_batch_size = 0;
    this.unit_formula_id = this.mfr_list[index - 1]['id'];
  }
  countries: any = [];
  number_of_batches = 0;
  bfr_no;
  primaryPacking_materials = [];
  ConsumeableMaterial = [];
  getRawMaterialsByBfrNo(index) {
    // this.bfr_batch_size=this.bfr_list[index]['batch_formula_weight']
    this.bfr_batch_size = this.bfr_list[index - 1]['batch_formula_weight'];
    console.log(this.bfr_batch_size);
    console.log(this.bfr_batch_size);
    this.raw_materials = [];
    this.service
      .get(
        'planning/raw.php?type=get_raw_materials_by_bfr_no&bfr_no=' +
          this.bfr_no
      )
      .subscribe((response) => {
        this.raw_materials = response['raw_materials'];
        this.ConsumeableMaterial = response['ConsumeableMaterial'];
        this.primaryPacking_materials = response['primaryPacking_materials'];
        this.countries = response['countries'];
      });
    this.number_of_batches = 0;
  }
  pack_list = [];
  countries1 = [];
  countriess: any = [];
  countries2: any = [];
  Pack_Size: any = [];
  Pack_Sizess: any = [];
  countries4: any = [];
  plan_for_market;
  getPackingMaterialsByBfrNo(index) {
    this.pack_list = [];
    this.countries1 = [];
    this.service
      .get(
        'planning/raw.php?type=get_packing_materials_by_mfr_id&bfr_no=' +
          this.bfr_no +
          '&unit_formula_id=' +
          this.unit_formula_id +
          '&market_type=' +
          this.plan_for_market
      )
      .subscribe((response) => {
        this.countriess = response;
        this.countries = response['pack_sizes'];
        this.countries2 = response['pack_sizes']['pack_size1'];
        this.countries1 = response['packing_materials'];
        this.Pack_Size = response['pack_size1'];
        this.Pack_Sizess = this.Pack_Size;

        this.countries4 = response['pack_size1']['packing_materials'];
      });
    // this.bfr_batch_size = this.bfr_list[index-1]['batch_formula_weight'];
  }
  selectedPachList = [];
  countries0: any = [];
  unit_formula_dtl_id;
  getPackList(index) {
    this.pack_list = [];
    this.selectedPachList = [];
    this.Pack_Sizess = this.Pack_Size;
    console.log(this.Pack_Sizess);
    // this.pack_list = this.countries0[index - 1]['packing_materials'];
    this.unit_formula_dtl_id = this.Pack_Size[index - 1]['id'];
    console.log('unit_formula_dtl_id='+ this.unit_formula_dtl_id)
  }
  idps;
  idps_unit;
  packs;
  pack_size1 = '';
  isLoader=false;
  get_pack_size_by_mfrno(index) {
    this.isLoader=true;
    this.pack_list = [];
    this.countries1 = [];
    this.Pack_Size = [];
    // this.unit_formula_dtl_id=0;
    this.idps = this.Pack_Sizess[index - 1]['pack_size'];
    this.idps_unit = this.Pack_Sizess[index - 1]['unit'];
    this.service
      .get(
        'planning/raw.php?type=get_pack_size_by_mfrno&unit_formula_dtl_id=' +
          this.pack_size1 +
          '&bfr_no=' +
          this.bfr_no
      )
      .subscribe((response) => {
        this.packs = response;
      });
    console.log(this.idps);

    setTimeout(() => {
      this.setNoOfBatches();
    }, 5000); // 5000 milliseconds = 5 seconds
   
  }
  is_both_types = false;
  sale_qty = 0;
  ps_qty = 0;
  setBatchQty(idx) {
    this.is_both_types = false;
    if (idx - 1 == 0) {
      this.sale_qty = this.bfr_batch_size;
      this.ps_qty = 0;
    } else if (idx - 1 == 1) {
      this.sale_qty = 0;
      this.ps_qty = this.bfr_batch_size;
    } else {
      this.is_both_types = true;
      this.sale_qty = 0;
      this.ps_qty = 0;
    }
  }
  setpsQtyBatchSize() {
    if (Number(this.sale_qty) >= Number(this.bfr_batch_size)) {
      this.sale_qty = this.bfr_batch_size;
      this.ps_qty = 0;
    } else {
      this.ps_qty = Number(this.bfr_batch_size) - Number(this.sale_qty);
    }
  }
  country_configuration = [];
  addCountry(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = data.value;
    temp['packs1234'] = this.idps;
    if (temp['ps_qty'] === null || temp['ps_qty'] === undefined) {
      temp['ps_qty'] = 'NA';
    }
    if (temp['sale_qty'] === null || temp['sale_qty'] === undefined) {
      temp['sale_qty'] = 'NA';
    }

    console.log(temp);

    //
    // Create a separate temp1 object
    let temp1 = {};
    temp1['packs'] = this.packs;
    temp1['packs1234'] = this.idps;
    temp1['idps_unit'] = this.idps_unit;

    // Merge the properties of temp and temp1 into a new object
    let combinedObject = { ...temp, ...temp1 };

    // Include combinedObject in the country_configuration array
    this.country_configuration.push(combinedObject);

    // Optionally, you can print this.country_configuration if needed
    console.log('country_configuration:', this.country_configuration);

    // Combine all 'packs' arrays into a single array
    let combinedPacks = this.country_configuration.reduce(
      (accumulator, currentValue) => {
        if (currentValue.packs) {
          return accumulator.concat(currentValue.packs);
        }
        return accumulator;
      },
      []
    );

    // Print the combined 'packs' array
    console.log('combined packs:', combinedPacks);

    // Update this.packs with the combinedPacks array if needed
    this.packs = combinedPacks;

    data.resetForm();
  }
  planned_qty;
  setNoOfBatches() {
    if (this.plan_type != 'Total Qty Wise') {
      return;
    }
    console.log(' :>> ');
    let bqty = Number(this.planned_qty) / Number(this.bfr_batch_size);
    if (Number(bqty) > 0) {
      let round = Math.round(bqty);
      this.number_of_batches = round;
      this.calculateBatchQty();
    }
  }
  groupedMaterials = [];
  multi_packing;
  pack_multiple_countries;
  lowest_batch;
  can_plan_qty;
  final_packing_materials_list = [];
  calculateBatchQty() {
    this.groupedMaterials = [];

    // this.planned_qty = 0;
    // /number_of_batches
    let min_batch = [];
    for (let i = 0; i < this.raw_materials.length; i++) {
      let qty = Number(this.raw_materials[i]['batch_qty'] * Number(this.number_of_batches));
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
      // console.log(minBatch);
      // console.log(  this.raw_materials[i]['can_plan_batches']);
      // console.log(  this.raw_materials['can_plan_batches']);
    }
    let temp = {}; // Initialize the 'temp' object if not already defined

    for (let i = 0; i < this.ConsumeableMaterial.length; i++) {
      this.ConsumeableMaterial[i]['plan'] = parseFloat(
        this.ConsumeableMaterial[i]['total_qty'] + ''
      ).toFixed(2);
      this.ConsumeableMaterial[i]['batch_qtyqqqq'] =
        (Number(this.selectresult['bom_batch_size']) *
          Number(this.ConsumeableMaterial[i]['total_qty'])) /
        Number(this.selectresult['bom_batch_size']);
      let can_plan_batches =
        Number(this.ConsumeableMaterial[i]['avbl_stock']) /
        Number(this.ConsumeableMaterial[i]['batch_qtyqqqq']);
      let minBatch = Math.round(can_plan_batches);
      this.ConsumeableMaterial[i]['plan'] = (
        Number(this.ConsumeableMaterial[i]['batch_qtyqqqq']) *
        Number(this.number_of_batches)
      ).toFixed(2);
      this.ConsumeableMaterial[i]['can_plan_batches'] = minBatch;
      const balanceQty = Number(this.ConsumeableMaterial[i]['avbl_stock']);
      const planQty = Number(this.ConsumeableMaterial[i]['plan']);
      const shortQty = balanceQty - planQty;
      this.ConsumeableMaterial[i]['short_qt'] = shortQty;
    }
    console.log('this.ConsumeableMaterial :>> ', this.ConsumeableMaterial);
    for (let i = 0; i < this.primaryPacking_materials.length; i++) {
      this.primaryPacking_materials[i]['plan'] = parseFloat(
        this.primaryPacking_materials[i]['total_qty'] + ''
      ).toFixed(2);
      this.primaryPacking_materials[i]['batch_qtyqqqq'] =
        (Number(this.selectresult['bom_batch_size']) *
          Number(this.primaryPacking_materials[i]['total_qty'])) /
        Number(this.selectresult['bom_batch_size']);
      let can_plan_batches =
        Number(this.primaryPacking_materials[i]['avbl_stock']) /
        Number(this.primaryPacking_materials[i]['batch_qtyqqqq']);
      let minBatch = Math.round(can_plan_batches);
      this.primaryPacking_materials[i]['plan'] = (
        Number(this.primaryPacking_materials[i]['batch_qtyqqqq']) *
        Number(this.number_of_batches)
      ).toFixed(2);
      this.primaryPacking_materials[i]['can_plan_batches'] = minBatch;
      const balanceQty = Number(this.primaryPacking_materials[i]['avbl_stock']);
      const planQty = Number(this.primaryPacking_materials[i]['plan']);
      const shortQty = balanceQty - planQty;
      this.primaryPacking_materials[i]['short_qt'] = shortQty;
    }
    console.log('this.ConsumeableMaterial :>> ', this.ConsumeableMaterial);

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
      } else if (this.pack_multiple_countries == 'No') {
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
        for (let i = 0; i < this.country_configuration.length; i++) {
          const packs = this.country_configuration[i];

          for (let j = 0; j < packs['packs'].length; j++) {
            let idps = 0;

            if (this.country_configuration[i].idps_unit === 'gm') {
              idps = Number(this.country_configuration[i].packs1234 / 1000);
              console.log('idps=' + idps);
            } else {
              idps = Number(this.country_configuration[i].packs1234);
              console.log('idps=' + idps);
            }

            let qty0 = Number(this.country_configuration[i]['sale_qty']) / idps;
            console.log(
              'sale_qty=' + this.country_configuration[i]['sale_qty']
            );
            console.log('qty0=' + qty0);

            let qty = qty0 * Number(packs['packs'][j]['total_qty']);
            console.log('total_qty=' + packs['packs'][j]['total_qty']);
            console.log('qty=' + qty);

            packs['packs'][j]['plan'] = parseFloat(qty + '').toFixed(2);
            console.log('plan=' + packs['packs'][j]['plan']);
            packs['packs'][j]['batch_qtyqqqq'] =
              (Number(this.country_configuration[i]['sale_qty']) *
                Number(packs['packs'][j]['total_qty'])) /
              Number(packs['packs'][j]['batch_size']);
            console.log(packs['packs'][j]['batch_formula_weight']);
            console.log('batch_qtyqqqq=' + packs['packs'][j]['batch_qtyqqqq']);
          }
        }
      }

      this.packs[i]['can_plan'] =
        Number(this.packs[i]['balance_qty']) /
        Number(this.packs[i]['batch_qtyqqqq']);
      this.packs[i]['pm_overages'] = Number(this.packs[i]['pm_overages']);
      const balanceQty = Number(this.packs[i]['balance_qty']);
      const planQty = Number(this.packs[i]['plan']);
      const shortQty = balanceQty - planQty;
      this.packs[i]['short_qt'] = shortQty;

      temp['packing_materials'].push(this.packs[i]);
    }
    const min = Math.min(...min_batch);
    this.lowest_batch = min < 0 ? 0 : min;
    this.can_plan_qty = min < 0 ? 0 : min * this.bfr_batch_size;
    if (this.plan_type == 'Batch Wise') {
      this.planned_qty = this.bfr_batch_size * this.number_of_batches;
      console.log(this.planned_qty);
      console.log(this.bfr_batch_size);
      console.log(this.number_of_batches);
    }
    for (let k = 0; k < this.final_packing_materials_list.length; k++) {
      for (
        let i = 0;
        i < this.final_packing_materials_list[k]['packing_materials'].length;
        i++
      ) {
        let qty = Number(
          this.final_packing_materials_list[k]['packing_materials'][i][
            'batch_qty'
          ] * Number(this.number_of_batches)
        );
        this.final_packing_materials_list[k]['packing_materials'][i][
          'plan_qty'
        ] = parseFloat(qty + '').toFixed(2);
        let can_plan_batches =
          Number(
            this.final_packing_materials_list[k]['packing_materials'][i][
              'avbl_stock'
            ]
          ) /
          Number(
            this.final_packing_materials_list[k]['packing_materials'][i][
              'batch_qty'
            ]
          );
        this.final_packing_materials_list[k]['packing_materials'][i][
          'can_plan_batches'
        ] = Math.round(can_plan_batches);
        this.final_packing_materials_list[k]['packing_materials'][i][
          'shortage_qty'
        ] = (
          Number(
            this.final_packing_materials_list[k]['packing_materials'][i][
              'avbl_stock'
            ]
          ) -
          Number(
            this.final_packing_materials_list[k]['packing_materials'][i][
              'plan_qty'
            ]
          )
        ).toFixed(2);
      }
    }
    console.log('this.raw_materials :>> ', this.raw_materials);
    this.groupedMaterials = this.raw_materials.reduce((group, material) => {
      const { stage } = material;
      group[stage] = group[stage] ?? [];
      group[stage].push(material);
      return group;
    }, {});
     this.isLoader=false;
  }

  saveBatchPlan(data, based_on) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['batch_size'] = this.bfr_batch_size;
    temp['packSize'] = this.idps;
    temp['planned_qty'] = this.planned_qty;
    temp['number_of_batches'] = this.number_of_batches;
    temp['bfr_no'] = this.bfr_no;
    temp['unit_formula_id'] = this.unit_formula_id;
    temp['unit_formula_dtl_id'] = this.unit_formula_dtl_id;
    temp['plan_type'] = this.plan_type;
    temp['plan_based_on'] = based_on;
    temp['raw_materials'] = this.raw_materials;
    temp['packing_materials'] = this.packs;
    temp['primaryPacking_materials'] = this.primaryPacking_materials;
    temp['ConsumeableMaterial'] = this.ConsumeableMaterial;
    temp['selectedorders'] = this.selectedItems;
    temp['product_type'] = this.selectresult['product_type'];
    temp['product_code'] = this.selectresult['product_code'];
    temp['order_no'] = this.selectresult['order_no'];
    temp['client_code'] = this.selectresult['client_code'];
    temp['po_no'] = this.selectresult['po_no'];
    temp['plan_client_name'] = this.selectresult['TrdNm'];
    temp['po_date'] = this.selectresult['po_date'];

    console.log(temp);
    this.service
      .post('planning/raw.php?type=save_plan', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          data.resetForm();
          alertify.success(this.service.t('common.savedSuccess'));
          // this.router.navigate(['/mrp']);
          this.isView=false;
           this.getPOsLog();
        } else {
          alertify.error('An error occured, Please try again');
        }
      });
  }
  isClients = false;
  checkRequiredfor(value) {
    if (value !== 'Own') {
      this.isClients = true;
    } else {
      this.isClients = false;
    }
  }

  private EXCEL_TYPE =
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=UTF-8';

  exportExcel(): void {
    const exportData = this.pendingpo.map((pending, index) => ({
      'Sr. No.': index + 1,
      'Order No': pending.order_no,
      'Product Name': `${pending.product_name} (${pending.product_code})`,
      'Order Qty': `${pending.order_qty} ${pending.unit}`,
      'Pack Size': `${pending.pack_size?.pack_size ?? ''} ${
        pending.pack_size?.unit ?? ''
      }`,
      'Medicap Lot Nos': pending.batch_nos?.map((b) => b.batch_number).join(', '),
      'Stock Qty': pending.stock_qty,
      'Remaining Qty': pending.order_qty - pending.stock_qty,
      'Tentative Commencement Date': '', // If you have it
      'Actual Plan Date': pending.planDates
        ?.map((d) => this.formatDate(d.approve_date))
        .join(', '),
      'No of Batches Plan': pending.total_batches
        ?.map((t) => t.total_batches)
        .join(', '),
      'Actual Start Date': pending.startsDates
        ?.map((s) => this.formatDate(s.approved_date))
        .join('\r\n'),
      'PO Date': this.formatDate(pending.po_date),
    }));

    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(exportData);
    const workbook: XLSX.WorkBook = {
      Sheets: { PendingPO: worksheet },
      SheetNames: ['PendingPO'],
    };
    const excelBuffer: any = XLSX.write(workbook, {
      bookType: 'xlsx',
      type: 'array',
    });
    const fileName = `PendingPO_${new Date().getTime()}.xlsx`;
    this.saveAsExcelFile(excelBuffer, fileName);
  }

  formatDate(dateStr: string): string {
    if (!dateStr || isNaN(new Date(dateStr).getTime())) {
      return '-'; // 👈 Return "N/A" if the date is empty or invalid
    }
    const date = new Date(dateStr);
    return `${date.getDate().toString().padStart(2, '0')}-${(
      date.getMonth() + 1
    )
      .toString()
      .padStart(2, '0')}-${date.getFullYear()}`;
  }

  saveAsExcelFile(buffer: any, fileName: string): void {
    const data: Blob = new Blob([buffer], { type: this.EXCEL_TYPE }); // use this.EXCEL_TYPE now
    FileSaver.saveAs(data, fileName);
  }



  SubmitStages(data){
    let temp=data.value;
    temp['wordOrderId']=this.wordOrderId;
    console.log(temp);
     this.service
      .post('planning/raw.php?type=save_planStages', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] == 'success') {
          data.resetForm();
          alertify.success(this.service.t('common.savedSuccess'));
        
        } else {
          alertify.error('An error occured, Please try again');
        }
      });
  }
}
