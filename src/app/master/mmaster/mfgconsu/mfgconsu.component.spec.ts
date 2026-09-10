import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MfgconsuComponent } from './mfgconsu.component';

describe('MfgconsuComponent', () => {
  let component: MfgconsuComponent;
  let fixture: ComponentFixture<MfgconsuComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MfgconsuComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MfgconsuComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
