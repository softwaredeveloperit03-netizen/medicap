import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReceivepofoComponent } from './receivepofo.component';

describe('ReceivepofoComponent', () => {
  let component: ReceivepofoComponent;
  let fixture: ComponentFixture<ReceivepofoComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReceivepofoComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReceivepofoComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
