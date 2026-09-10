import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RndCostCenterComponent } from './rnd-cost-center.component';

describe('RndCostCenterComponent', () => {
  let component: RndCostCenterComponent;
  let fixture: ComponentFixture<RndCostCenterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RndCostCenterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RndCostCenterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
