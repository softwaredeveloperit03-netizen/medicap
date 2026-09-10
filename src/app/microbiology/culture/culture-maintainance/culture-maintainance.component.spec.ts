import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CultureMaintainanceComponent } from './culture-maintainance.component';

describe('CultureMaintainanceComponent', () => {
  let component: CultureMaintainanceComponent;
  let fixture: ComponentFixture<CultureMaintainanceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CultureMaintainanceComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(CultureMaintainanceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
