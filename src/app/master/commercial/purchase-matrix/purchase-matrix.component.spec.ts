import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PurchaseMatrixComponent } from './purchase-matrix.component';

describe('PurchaseMatrixComponent', () => {
  let component: PurchaseMatrixComponent;
  let fixture: ComponentFixture<PurchaseMatrixComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PurchaseMatrixComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PurchaseMatrixComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
