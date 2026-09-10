import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CurrDashbordComponent } from './curr-dashbord.component';

describe('CurrDashbordComponent', () => {
  let component: CurrDashbordComponent;
  let fixture: ComponentFixture<CurrDashbordComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CurrDashbordComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CurrDashbordComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
