import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DutypaymentComponent } from './dutypayment.component';

describe('DutypaymentComponent', () => {
  let component: DutypaymentComponent;
  let fixture: ComponentFixture<DutypaymentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DutypaymentComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DutypaymentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
