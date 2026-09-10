import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CaliblabelComponent } from './caliblabel.component';

describe('CaliblabelComponent', () => {
  let component: CaliblabelComponent;
  let fixture: ComponentFixture<CaliblabelComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CaliblabelComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CaliblabelComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
