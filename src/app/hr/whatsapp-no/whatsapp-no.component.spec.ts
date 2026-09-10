import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WhatsappNoComponent } from './whatsapp-no.component';

describe('WhatsappNoComponent', () => {
  let component: WhatsappNoComponent;
  let fixture: ComponentFixture<WhatsappNoComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WhatsappNoComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WhatsappNoComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
