import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PrimixMasterComponent } from './primix-master.component';

describe('PrimixMasterComponent', () => {
  let component: PrimixMasterComponent;
  let fixture: ComponentFixture<PrimixMasterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PrimixMasterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PrimixMasterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
