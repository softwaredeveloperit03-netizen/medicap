import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WowiseComponent } from './wowise.component';

describe('WowiseComponent', () => {
  let component: WowiseComponent;
  let fixture: ComponentFixture<WowiseComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WowiseComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WowiseComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
