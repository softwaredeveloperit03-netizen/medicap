import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReceivepofologComponent } from './receivepofolog.component';

describe('ReceivepofologComponent', () => {
  let component: ReceivepofologComponent;
  let fixture: ComponentFixture<ReceivepofologComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReceivepofologComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReceivepofologComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
