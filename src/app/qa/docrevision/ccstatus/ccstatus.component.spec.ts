import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CcstatusComponent } from './ccstatus.component';

describe('CcstatusComponent', () => {
  let component: CcstatusComponent;
  let fixture: ComponentFixture<CcstatusComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CcstatusComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CcstatusComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
