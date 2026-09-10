import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PerfchkrecordComponent } from './perfchkrecord.component';

describe('PerfchkrecordComponent', () => {
  let component: PerfchkrecordComponent;
  let fixture: ComponentFixture<PerfchkrecordComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PerfchkrecordComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PerfchkrecordComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
