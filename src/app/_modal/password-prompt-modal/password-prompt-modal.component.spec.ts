import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PasswordPromptModalComponent } from './password-prompt-modal.component';

describe('PasswordPromptModalComponent', () => {
  let component: PasswordPromptModalComponent;
  let fixture: ComponentFixture<PasswordPromptModalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PasswordPromptModalComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PasswordPromptModalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
