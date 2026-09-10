import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MrpHistoryComponent } from './mrp-history.component';

describe('MrpHistoryComponent', () => {
  let component: MrpHistoryComponent;
  let fixture: ComponentFixture<MrpHistoryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MrpHistoryComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(MrpHistoryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
